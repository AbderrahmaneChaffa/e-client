<?php

namespace App\Console\Commands;

use App\Services\Notifications\AlertNotificationService;
use Illuminate\Console\Command;

class SyncNotificationAlerts extends Command
{
    protected $signature = 'notifications:sync-alerts
        {--clients : Sync unpaid and overdue client invoice alerts}
        {--admins : Sync pending imports, failed imports, and open admin alerts}
        {--limit= : Maximum client invoices to scan}';

    protected $description = 'Synchronize database notifications for invoice, import, and integrity alerts.';

    public function handle(AlertNotificationService $alerts): int
    {
        $clients = (bool) $this->option('clients');
        $admins = (bool) $this->option('admins');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        if (! $clients && ! $admins) {
            $clients = true;
            $admins = true;
        }

        if ($clients) {
            $alerts->notifyClientOpenInvoiceAlerts($limit);
            $this->info('Client invoice alerts synchronized.');
        }

        if ($admins) {
            $alerts->notifyAdminOutstandingAlerts();
            $this->info('Admin alerts synchronized.');
        }

        return self::SUCCESS;
    }
}
