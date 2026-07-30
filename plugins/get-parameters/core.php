<?php

$api_get_plugins = array();
$api_get_param = array();

function RegisterGetParameterPlugin($name, $definition)
{
    global $api_get_plugins, $api_get_param;

    $api_get_plugins[$name] = $definition;
    foreach ($definition['parameters'] ?? array() as $parameter => $rules) {
        $api_get_param[$parameter] = $rules;
    }
}

function LoadGetParameterModules($modules)
{
    foreach ($modules as $module => $enabled) {
        if ($enabled) {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $module)) {
                Error('Wrong GET parameter module name: '.$module);
            }
            include_once('get-parameters/'.$module.'.php');
        }
    }
}

function ReadGetParameters()
{
    global $api_get_param, $api_get;

    foreach ($api_get_param as $key => $definition) {
        if (!isset($_GET[$key])) {
            $api_get[$key] = $definition[1];
            continue;
        }

        $value = strtolower($_GET[$key]);
        $options = explode(',', $definition[0]);

        if (in_array('int', $options)) {
            if (!is_numeric($value)) {
                $value = $definition[1];
            }
            if (in_array('abs', $options)) {
                $value = abs($value);
            }
            if (in_array('nz', $options) && $value == 0) {
                $value = $definition[1];
            }
        }

        foreach ($options as $option) {
            if (substr($option, 0, 4) == 'max:' && $value > substr($option, 4)) {
                $value = substr($option, 4);
            }
        }

        if (isset($definition[2])) {
            $allowed = explode(',', $definition[2]);
            if (!in_array($value, $allowed)) {
                $value = $definition[1];
            }
        }

        $api_get[$key] = $value;
    }
}

function RunGetParameterPluginHook($hook)
{
    global $api_get_plugins;

    foreach ($api_get_plugins as $plugin) {
        if (isset($plugin[$hook]) && is_callable($plugin[$hook])) {
            $plugin[$hook]();
        }
    }
}

function PrepareGetParameterPlugins()
{
    RunGetParameterPluginHook('prepare');
}

function BeforeQueryGetParameterPlugins()
{
    RunGetParameterPluginHook('before_query');
}

function WrapQueryGetParameterPlugins()
{
    RunGetParameterPluginHook('wrap_query');
}

function AddAnswerGetParameterPlugins()
{
    RunGetParameterPluginHook('answer');
}

LoadGetParameterModules($api_plugins['get-parameters']);
RegisterApiPluginHook('read_request', 'ReadGetParameters');
RegisterApiPluginHook('prepare', 'PrepareGetParameterPlugins');
RegisterApiPluginHook('before_query', 'BeforeQueryGetParameterPlugins');
RegisterApiPluginHook('wrap_query', 'WrapQueryGetParameterPlugins');
RegisterApiPluginHook('answer', 'AddAnswerGetParameterPlugins');

?>
