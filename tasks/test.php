<?php

class Travis_Test_Task {

    public function run($arguments)
    {
        // echo help
    }

    public function installapp($arguments)
    {   
        \Session::load();
        
        $files = glob("./bundles/*");

        $string = "\n".'<?php' ."\n".'return array(';
        $modules_list = array();
        foreach($files as $file)
        {
            if(is_dir($file))
            {
                $modules_list[] = basename($file);
                echo "Found module [".basename($file)."]\n";
                $string .= '\''.basename($file).'\' => array('."\n";
                $string .= '\'auto\' => true,'."\n";
                $string .= '\'handles\' => \''.basename($file).'\''."\n";
                $string .= '),'."\n";
            }
        }
        $string .= ');';
        $bundles_file = './application/bundles.php';
        $new_bundles_file = fopen($bundles_file, 'w') or die("can't open file");
        fwrite($new_bundles_file, $string);
        fclose($new_bundles_file);
        echo "Bundles file updated successfully.\n";

        require path('sys').'cli/dependencies.php';

        echo "Installing migrations table...\n";
        \Laravel\CLI\Command::run(array('migrate:install'));
        echo "\nReseting migrations...\n";
        \Laravel\CLI\Command::run(array('migrate:reset'));
        echo "\nRunnign migrations...\n";
        \Laravel\CLI\Command::run(array('migrate'));

        foreach ($modules_list as $module) 
        {
            Bundle::register($module);
            echo "\nRunning migration for [".$module."]\n";
            \Laravel\CLI\Command::run(array('migrate', $module));
            Bundle::disable($module);
        }
        echo "\n";

        Bundle::register('modules');
        Bundle::start('modules');
        foreach ($modules_list as $module => $module_path)
        {
            $mod = \Modules\Module::make($module)->is_valid();

            $new_bundle = new \Modules\Model\Module;
            $new_bundle->name        = $mod->name;
            $new_bundle->slug        = $mod->slug;
            $new_bundle->description = isset($mod->description) ? $mod->description : '';
            $new_bundle->version     = $mod->version ;
            $new_bundle->is_frontend = isset($mod->is_frontend) ? $mod->is_frontend : 0;
            $new_bundle->is_backend  = isset($mod->is_backend) ? $mod->is_backend : 0;
            $new_bundle->is_core     = isset($mod->is_core) ? 1 : 0;;
            $new_bundle->required    = $mod->decode('required');
            $new_bundle->recommended = $mod->decode('recommended');
            $new_bundle->options     = $mod->decode('options'); 
            $new_bundle->roles       = $mod->decode('roles');
            $new_bundle->menu        = $mod->decode('menu');
            $new_bundle->enabled     = 1;
            $new_bundle->save();
        }
        Bundle::disable('modules');

        //run all tasks
        $files = glob("./bundles/*");
        foreach($files as $file)
        {
            if(is_dir($file))
            {
                $schema_path = './bundles/'.basename($file).'/tasks/schema.php'; 
                    
                if(\File::exists($schema_path))
                {
                    $action = 'install';
                    include_once $schema_path;
                    // Does the class exists?
                    $class = basename($file).'_Schema_Task';
                    if(class_exists($class))
                    {
                        $schema_class = new $class;
                        // The action is callable?
                        if(is_callable(array($schema_class, $action)))
                        {
                            echo "Seeding database for [".basename($file)."]\n";
                            //$schema_class->$action();
                        }
                    }
                }
            }
        }

        echo "Application is ready for tests...\n";
    }
}