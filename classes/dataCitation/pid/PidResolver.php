<?php

/**
 * @file classes/dataCitation/pid/PidResolver.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PidResolver
 *
 * @ingroup dataCitation
 *
 * @brief Resolves identifier type strings to their corresponding PID class.
 */

namespace PKP\dataCitation\pid;

use PKP\pid\Accession;
use PKP\pid\Ark;
use PKP\pid\Arxiv;
use PKP\pid\Doi;
use PKP\pid\Ecli;
use PKP\pid\Handle;
use PKP\pid\Isbn;
use PKP\pid\Issn;
use PKP\pid\Pmcid;
use PKP\pid\Pmid;
use PKP\pid\Purl;
use PKP\pid\Url;
use PKP\pid\Uuid;

class PidResolver
{
    /**
     * Resolve an identifier type string to a PID class name.
     *
     * @param string $type e.g. 'DOI', 'ARXIV', 'Handle'
     *
     * @return string|null The PID class name, or null if not supported.
     *
     */
    public static function resolveByIdentifierType(string $type): ?string
    {
        return match($type) {
            'DOI'       => Doi::class,
            'ARXIV'     => Arxiv::class,
            'Handle'    => Handle::class,
            'ISSN'      => Issn::class,
            'ISBN'      => Isbn::class,
            'PMID'      => Pmid::class,
            'PMCID'     => Pmcid::class,
            'ARK'       => Ark::class,
            'ECLI'      => Ecli::class,
            'UUID'      => Uuid::class,
            'URI'       => Url::class,
            'PURL'      => Purl::class,
            'Accession' => Accession::class,
            default     => null,
        };
    }
}
