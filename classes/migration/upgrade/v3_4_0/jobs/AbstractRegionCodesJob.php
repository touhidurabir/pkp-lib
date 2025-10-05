<?php

/**
 * @file classes/migration/upgrade/v3_4_0/jobs/AbstractRegionCodesJob.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AbstractRegionCodesJob
 *
 * @ingroup jobs
 *
 * @brief Base class for jobs that convert region codes in geo metrics tables using the region_mapping_tmp table.
 */

namespace PKP\migration\upgrade\v3_4_0\jobs;

use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use PKP\jobs\BaseJob;

abstract class AbstractRegionCodesJob extends BaseJob
{
    /**
     * Create a new job instance, using the range of IDs to consider for this update.
     */
    public function __construct(protected int $startId, protected int $endId)
    {
        parent::__construct();
    }

    /** The geo metrics table to update (e.g. metrics_submission_geo_daily). */
    abstract protected function getTable(): string;

    /** The alias to use for the geo metrics table in the query; required to qualify the column in the PostgreSQL updateFrom call. */
    abstract protected function getAlias(): string;

    /** The primary key column of the geo metrics table. */
    abstract protected function getIdColumn(): string;

    /** The column in region_mapping_tmp to join on (fips or pkp_fips). */
    abstract protected function getJoinColumn(): string;

    /** The column in region_mapping_tmp to read the replacement value from (pkp_fips or iso). */
    abstract protected function getUpdateValue(): string;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $alias = $this->getAlias();
        $query = DB::table($this->getTable() . ' as ' . $alias)
            ->join('region_mapping_tmp as rm', function ($join) use ($alias) {
                $join->on("{$alias}.country", '=', 'rm.country')
                    ->on("{$alias}.region", '=', 'rm.' . $this->getJoinColumn());
            })
            ->whereBetween("{$alias}." . $this->getIdColumn(), [$this->startId, $this->endId]);

        $updateValue = DB::raw('rm.' . $this->getUpdateValue());
        if (DB::connection() instanceof PostgresConnection) {
            $query->updateFrom(["{$alias}.region" => $updateValue]);
        } else {
            $query->update(["{$alias}.region" => $updateValue]);
        }
    }
}
