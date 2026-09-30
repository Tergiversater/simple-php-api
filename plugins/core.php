<?php

$api_plugin_hooks = array();

function RegisterApiPluginHook($hook, $callback)
{
    global $api_plugin_hooks;
    $api_plugin_hooks[$hook][] = $callback;
}

function RunApiPluginHook($hook)
{
    global $api_plugin_hooks;

    foreach ($api_plugin_hooks[$hook] ?? array() as $callback) {
        if (is_callable($callback)) {
            $callback();
        }
    }
}

if (!isset($api_plugins) && isset($api_get_param)) {
    $api_plugins = array(
        'get-parameters' => array(
            'mode' => true,
            'number' => true,
            'page' => true
        )
    );
}

foreach ($api_plugins ?? array() as $plugin => $modules) {
    if (!is_array($modules) || !in_array(true, $modules, true)) {
        continue;
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $plugin)) {
        Error('Wrong plugin name: '.$plugin);
    }

    $plugin_core = stream_resolve_include_path($plugin.'/core.php');
    if ($plugin_core === false) {
        Error('Plugin core not found: '.$plugin);
    }

    include_once($plugin_core);
}

?>
