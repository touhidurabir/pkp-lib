<?php

/**
 * @file classes/migration/upgrade/v3_4_0/jobs/CleanTmpChangesForRegionCodesFixes.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CleanTmpChangesForRegionCodesFixes
 *
 * @ingroup jobs
 *
 * @brief Drops temporary indexes on the geo metrics tables, restores the region column length to varchar(3), and drops the region_mapping_tmp table.
 */

namespace PKP\migration\upgrade\v3_4_0\jobs;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\jobs\BaseJob;

class CleanTmpChangesForRegionCodesFixes extends BaseJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // drop the temporary indexes
        Schema::table('metrics_submission_geo_daily', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('metrics_submission_geo_daily');
            if (array_key_exists('metrics_submission_geo_daily_tmp_index', $indexesFound)) {
                $table->dropIndex(['tmp']);
            }
        });
        Schema::table('metrics_submission_geo_monthly', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('metrics_submission_geo_monthly');
            if (array_key_exists('metrics_submission_geo_monthly_tmp_index', $indexesFound)) {
                $table->dropIndex(['tmp']);
            }
        });

        // restore the region column length to varchar(3); safe because all pkp-prefixed codes
        // have been converted to ISO codes (max 3 chars) by the preceding Fix jobs in the chain
        Schema::table('metrics_submission_geo_daily', function (Blueprint $table) {
            $table->string('region', 3)->change();
        });
        Schema::table('metrics_submission_geo_monthly', function (Blueprint $table) {
            $table->string('region', 3)->change();
        });

        // drop the temporary table
        if (Schema::hasTable('region_mapping_tmp')) {
            Schema::drop('region_mapping_tmp');
        }
    }
}
