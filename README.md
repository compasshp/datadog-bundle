# DataDog Bundle

This bundle allows you to automatically track user information in DataDog's APM and ASM modules.

**DataDog APM**

For DataDog APM, it will automatically pass in the session ID, and -- if logged in -- the currently logged in username.
You can additionally specify additional properties to pass to APM.

**DataDog ASM**

If enabled, for DataDog Application Security Monitoring, all success and failure login attempts will be passed to the
datadog agent. You can additionally specify additional properties to pass to APM.

# Installation

## Prerequisites

The [DataDog Agent](https://docs.datadoghq.com/tracing/trace_collection/dd_libraries/php/?tab=containers) needs to be
configured in your application with tracing enabled.

If you are using Application Security Monitoring, the agent should be configured with the `--enable-appsec` argument.

## Get the bundle

Let Composer download and install the bundle by running

```sh
composer require compasshp/datadog-bundle
```

in a shell.

## Enable the bundle

Register the bundle (you most likely don't want this bundle enable)

```php
// in config/bundles.php
return [
	// ...
	Compass\DatadogBundle\CompassDatadogBundle::class => ['all' => true],
];
```

## Configure

Create the configuration file:

```yaml
# config/packages/compass_datadog.yaml

# The DataDog agent is typically not available in your test environment.
when@test:
  compass_datadog:
    tracing:
      enabled: false
    appsec:
      enabled: false

compass_datadog:
  tracing:
    enabled: true
    user_entity: App\Entity\User
    user_properties: [ firstName, lastName, email ] # specify any additional properties you want traced.  Username is always traced when someone is logged in.
  appsec:
    enabled: true # default is false
```

And that's it!  Once configured, your application will automatically begin sending additional metadata to DataDog.

