<?php

/**
 * @file classes/migration/upgrade/v3_4_0/jobs/PreFixRegionCodesMonthly.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreFixRegionCodesMonthly
 *
 * @ingroup jobs
 *
 * @brief Marks FIPS region codes with a 'pkp-' prefix to identify them for conversion to ISO codes.
 */

namespace PKP\migration\upgrade\v3_4_0\jobs;

class PreFixRegionCodesMonthly extends AbstractRegionCodesJob
{
    protected function getTable(): string
    {
        return 'metrics_submission_geo_monthly';
    }
    protected function getAlias(): string
    {
        return 'gm';
    }
    protected function getIdColumn(): string
    {
        return 'metrics_submission_geo_monthly_id';
    }
    protected function getJoinColumn(): string
    {
        return 'fips';
    }
    protected function getUpdateValue(): string
    {
        return 'pkp_fips';
    }
}
