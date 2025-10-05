<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I8866_DispatchRegionCodesFixingJobs.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I8866_DispatchRegionCodesFixingJobs
 *
 * @brief Dispatches background jobs to convert FIPS region codes to ISO codes in the geo metrics tables, if any non-empty region codes exist.
 */

namespace PKP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;
use PKP\migration\upgrade\v3_4_0\jobs\CleanTmpChangesForRegionCodesFixes;
use PKP\migration\upgrade\v3_4_0\jobs\FixRegionCodesDaily;
use PKP\migration\upgrade\v3_4_0\jobs\FixRegionCodesMonthly;
use PKP\migration\upgrade\v3_4_0\jobs\PreFixRegionCodesDaily;
use PKP\migration\upgrade\v3_4_0\jobs\PreFixRegionCodesMonthly;
use PKP\migration\upgrade\v3_4_0\jobs\RegionMappingTmpInsert;
use Throwable;

class I8866_DispatchRegionCodesFixingJobs extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        if (DB::table('metrics_submission_geo_monthly')->where('region', '<>', '')->exists() ||
            DB::table('metrics_submission_geo_daily')->where('region', '<>', '')->exists()) {

            // create a temporary table for the FIPS-ISO mapping
            if (!Schema::hasTable('region_mapping_tmp')) {
                Schema::create('region_mapping_tmp', function (Blueprint $table) {
                    $table->string('country', 2);
                    $table->string('fips', 3);
                    $table->string('pkp_fips', 7);
                    $table->string('iso', 3);
                    $table->index(['country', 'fips']);
                    $table->index(['country', 'pkp_fips']);
                });
            }

            // temporary change the length of the region columns, because we will add prefix 'pkp-'
            Schema::table('metrics_submission_geo_daily', function (Blueprint $table) {
                $table->string('region', 7)->change();
            });
            Schema::table('metrics_submission_geo_monthly', function (Blueprint $table) {
                $table->string('region', 7)->change();
            });

            // temporarily add an index on (country, region) to speed up the region code updates
            Schema::table('metrics_submission_geo_daily', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('metrics_submission_geo_daily');
                if (!array_key_exists('metrics_submission_geo_daily_tmp_index', $indexesFound)) {
                    $table->index(['country', 'region'], 'metrics_submission_geo_daily_tmp_index');
                }
            });
            Schema::table('metrics_submission_geo_monthly', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('metrics_submission_geo_monthly');
                if (!array_key_exists('metrics_submission_geo_monthly_tmp_index', $indexesFound)) {
                    $table->index(['country', 'region'], 'metrics_submission_geo_monthly_tmp_index');
                }
            });

            $geoDailyIdMax = DB::table('metrics_submission_geo_daily')
                ->max('metrics_submission_geo_daily_id') ?? 0;
            $geoMonthlyIdMax = DB::table('metrics_submission_geo_monthly')
                ->max('metrics_submission_geo_monthly_id') ?? 0;

            $chunkSize = 10000;
            $geoDailyChunksNo = ceil($geoDailyIdMax / $chunkSize);
            $geoMonthlyChunksNo = ceil($geoMonthlyIdMax / $chunkSize);

            // load all FIPS-ISO mappings into the temporary table, then dispatch chunk jobs
            $jobs = [new RegionMappingTmpInsert()];
            for ($i = 0; $i < $geoDailyChunksNo; $i++) {
                $startId = ($i * $chunkSize) + 1;
                $endId = min(($i + 1) * $chunkSize, $geoDailyIdMax);
                $jobs[] = new PreFixRegionCodesDaily($startId, $endId);
            }
            for ($i = 0; $i < $geoMonthlyChunksNo; $i++) {
                $startId = ($i * $chunkSize) + 1;
                $endId = min(($i + 1) * $chunkSize, $geoMonthlyIdMax);
                $jobs[] = new PreFixRegionCodesMonthly($startId, $endId);
            }
            for ($i = 0; $i < $geoDailyChunksNo; $i++) {
                $startId = ($i * $chunkSize) + 1;
                $endId = min(($i + 1) * $chunkSize, $geoDailyIdMax);
                $jobs[] = new FixRegionCodesDaily($startId, $endId);
            }
            for ($i = 0; $i < $geoMonthlyChunksNo; $i++) {
                $startId = ($i * $chunkSize) + 1;
                $endId = min(($i + 1) * $chunkSize, $geoMonthlyIdMax);
                $jobs[] = new FixRegionCodesMonthly($startId, $endId);
            }
            $jobs[] = new CleanTmpChangesForRegionCodesFixes();
            Bus::chain($jobs)
                ->catch(function (Throwable $e) {
                    // Temporary state (widened region columns, temp indexes, region_mapping_tmp table)
                    // is intentionally left in place so failed jobs can be retried.
                    // CleanTmpChangesForRegionCodesFixes will run once the chain completes successfully.
                    error_log('Error during region codes fixing jobs: ' . $e->getMessage());
                })
                ->dispatch();
        }
    }

    /**
     * Reverse the migration
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
