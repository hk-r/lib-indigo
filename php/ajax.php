<?php

    require_once( __DIR__ . '/fileManager.php' );
    require_once( __DIR__ . '/common.php' );
    
    $fileManager = new indigo\fileManager(null);
    $common = new indigo\common(null);

    $commit_hash = '';

    if (isset($_GET['branch_name']) && isset($_GET['path'])) {
    
        $current_dir = realpath('.');

        // masterディレクトリの絶対パス
        $master_real_path = $fileManager->normalize_path($fileManager->get_realpath($_GET['path']));
        // $master_real_path = 'error test';

        if ( $master_real_path ) {

            if ( chdir( $master_real_path ) ) {

                // コミットハッシュ値取得
                $command = 'git log --pretty=%h ' . $_GET['branch_name'] . ' -1';
                $ret = $common->command_execute($command, false);
                foreach ( (array)$ret['output'] as $data ) {
                    $commit_hash = $data;
                }

            } 
        }
        
        $data = array( 'commit_hash' => $commit_hash );
        chdir($current_dir);

        header("Content-type: application/javascript; charset=UTF-8");
        
        echo json_encode($data);

    }

?>