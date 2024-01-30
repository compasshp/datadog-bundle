<?php

declare(strict_types=1);

namespace Compass\DatadogBundle\Service;

use function datadog\appsec\track_user_login_failure_event;
use function datadog\appsec\track_user_login_success_event;
use function DDTrace\root_span;

/**
 * @internal
 */
class DatadogService
{
    public function addTraceMetadata(array $metadata)
    {
        $rootSpan = root_span();

        $metadata['dd.trace_id'] = \DDTrace\logs_correlation_trace_id();
        $metadata['dd.span_id'] = \dd_trace_peek_span_id();

        $rootSpan->meta = array_merge($rootSpan->meta, $metadata);
    }

    public function trackLoginSuccess(mixed $userId, ?array $metadata = [])
    {
        return track_user_login_success_event($userId, $metadata);
    }

    public function trackLoginFailure(mixed $userId, bool $exists, ?array $metadata = [])
    {
        return track_user_login_failure_event($userId, $exists, $metadata);
    }
}