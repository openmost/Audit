<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit;

use Piwik\Piwik;
use Piwik\Request;
use Piwik\Plugins\Audit\Checks\CheckRunner;
use Piwik\Plugins\Audit\Checklist\ChecklistLoader;
use Piwik\Plugins\Audit\Context\InstanceMetrics;
use Piwik\Plugins\Audit\Export\MarkdownExporter;
use Piwik\Plugins\Audit\Support\InlineMarkdown;
use Piwik\Url;

class Controller extends \Piwik\Plugin\ControllerAdmin
{
    public function index(): string
    {
        Piwik::checkUserHasSuperUserAccess();

        // AuditPremium runs every check: send the user there instead.
        if (Audit::isPremiumActivated()) {
            Url::redirectToUrl(Url::getCurrentQueryStringWithParametersModified([
                'module' => 'AuditPremium',
                'action' => 'index',
            ]));
        }

        $runner   = new CheckRunner(null, new InstanceMetrics());
        $report   = $runner->run();
        $metadata = (new ChecklistLoader())->loadMetadata();

        return $this->renderTemplate('index', [
            'report'            => $this->buildReportPayload($report, $metadata),
            'exportMarkdownUrl' => Url::getCurrentQueryStringWithParametersModified([
                'module' => 'Audit', 'action' => 'exportMarkdown',
            ]),
            'pluginVersion'     => $report->pluginVersion,
            'premiumUrl'        => Audit::PREMIUM_URL,
        ]);
    }

    public function exportMarkdown(): void
    {
        Piwik::checkUserHasSuperUserAccess();

        $runner   = new CheckRunner(null, new InstanceMetrics());
        $report   = $runner->run();
        $metadata = (new ChecklistLoader())->loadMetadata();

        $labels = [];
        foreach (($metadata['categories'] ?? []) as $cat) {
            if (!empty($cat['id'])) {
                $labels[$cat['id']] = $cat['label'] ?? $cat['id'];
            }
        }

        $plain    = Request::fromRequest()->getIntegerParameter('plain', 0) === 1;
        $exporter = new MarkdownExporter($labels, $plain);
        $body     = $exporter->render($report);
        $filename = sprintf('matomo-audit-%s%s.md', gmdate('Ymd-His'), $plain ? '-plain' : '');

        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        echo $body;
    }

    /**
     * Enrich the report payload with the checklist metadata and render
     * the inline-Markdown fields to HTML so the Vue front-end does not
     * need to pull a Markdown parser.
     */
    private function buildReportPayload(Checks\AuditReport $report, array $metadata): array
    {
        $payload = $report->toArray();

        foreach ($payload['findings'] as &$finding) {
            $finding['detailHtml'] = $finding['detail'] !== ''
                ? InlineMarkdown::toHtml($finding['detail'])
                : '';
            $finding['recommendationHtml'] = !empty($finding['recommendation'])
                ? InlineMarkdown::toHtml($finding['recommendation'])
                : '';
        }
        unset($finding);

        $payload['metadata'] = [
            'categories' => $metadata['categories'],
            'severities' => $metadata['severities'],
        ];
        return $payload;
    }
}
