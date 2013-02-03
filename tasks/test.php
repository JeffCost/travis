<?php

class Travis_Test_Task {

    public function run($arguments)
    {
        // echo help
    }

    public function installapp($arguments)
    {   
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
        $bundles_file = path('app').'bundles.php';
        $new_bundles_file = fopen($bundles_file, 'w') or die("can't open file");
        fwrite($new_bundles_file, $string);
        fclose($new_bundles_file);
        echo "Bundles file updated successfully.\n";

        $names = Bundle::names();
        var_dump($names);
        exit(0);

        require path('sys').'cli/dependencies.php';

        echo "Installing migrations table...\n";
        \Laravel\CLI\Command::run(array('migrate:install'));
        echo "\nReseting migrations...\n";
        \Laravel\CLI\Command::run(array('migrate:reset'));
        echo "\nRunnign migrations...\n";
        \Laravel\CLI\Command::run(array('migrate'));

        foreach ($modules_list as $module) 
        {
            echo "\nRunning migration for [".$module."]\n";
            \Laravel\CLI\Command::run(array('migrate', $module));
        }
        echo "\n";

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