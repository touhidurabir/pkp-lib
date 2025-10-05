<?php

/**
 * @file classes/migration/upgrade/v3_4_0/jobs/FixRegionCodesDaily.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FixRegionCodesDaily
 *
 * @ingroup jobs
 *
 * @brief Converts pkp-prefixed FIPS region codes to ISO codes using the temporary mapping table.
 */

namespace PKP\migration\upgrade\v3_4_0\jobs;

class FixRegionCodesDaily extends AbstractRegionCodesJob
{
    protected function getTable(): string
    {
        return 'metrics_submission_geo_daily';
    }
    protected function getAlias(): string
    {
        return 'gd';
    }
    protected function getIdColumn(): string
    {
        return 'metrics_submission_geo_daily_id';
    }
    protected function getJoinColumn(): string
    {
        return 'pkp_fips';
    }
    protected function getUpdateValue(): string
    {
        return 'iso';
    }
}
