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