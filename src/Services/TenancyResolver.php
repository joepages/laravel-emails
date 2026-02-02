<?php

declare(strict_types=1);

namespace Emails\Services;

class TenancyResolver
{
    private ?bool $multiTenantCache = null;

    /**
     * Check if the application uses multi-tenancy.
     * Detects presence of tenancy package.
     */
    public function isMultiTenant(): bool
    {
        if ($this->multiTenantCache !== null) {
            return $this->multiTenantCache;
        }

        // Check config override first
        $configMode = config('emails.tenancy_mode', 'auto');

        if ($configMode === 'single') {
            return $this->multiTenantCache = false;
        }

        if ($configMode === 'multi') {
            return $this->multiTenantCache = true;
        }

        // Auto-detect: Check if tenancy package is installed and configured
        $this->multiTenantCache = function_exists('tenancy')
            && config('tenancy.tenant_model') !== null;

        return $this->multiTenantCache;
    }
}
