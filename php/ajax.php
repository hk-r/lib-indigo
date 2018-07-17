<?php

    echo '★1';
    echo __DIR__;
    
    $gitManager = new gitManager();
    echo $gitManager -> name;//佐藤
    echo '★2';
    function __autoload($className){

      //$className（インスタンス生成時に読み込まれていないクラス名）
      $file = './' . $className . '.php';
      require $file;
    }
    echo '★3';
    if (isset($_POST['branch_name']) && isset($_POST['path'])) {
    
        // $this->common->debug_echo('■ jquery.php start');

        $commit_hash;

        $current_dir = realpath('.');

        // 指定ブランチ
        $branch_name = trim(str_replace("origin/", "", $_POST['branch_name']));
    echo '★4';
        if (!$branch_name) {
            // ディレクトリ移動に失敗
            throw new \Exception('Failed to get git commitHash. Get branch name failed.');
        }
        
        // masterディレクトリの絶対パス
        $master_real_path = isset($_POST['path']);

        if ( $master_real_path ) {

            // ディレクトリ移動
            if ( chdir( $master_real_path ) ) {

                // コミットハッシュ値取得
                $command = 'git log --pretty=%h ' . $branch_name . ' -1';
                $ret = $this->command_execute($command, false);

                foreach ( (array)$ret['output'] as $data ) {
                    $commit_hash = $data;
                }

            } else {

                chdir($current_dir);

                // ディレクトリ移動に失敗
                throw new \Exception('Failed to get git commitHash. Move to work directory failed.');
            }
        }

        chdir($current_dir);

        // $this->common->debug_echo('■ jquery.php');

        echo $commit_hash;

    } else {
        echo 'Ajax error! The parameter of "id" is not found.';
    }

?>