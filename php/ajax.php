<?php

namespace indigo;

class ajax
{
    public $options;

    /**
     * オブジェクト
     * @access private
     */
    private $fs, $common;

    /**
     * コンストラクタ
     * @param $options = オプション
     */
    public function __construct($options) {

        $this->options = json_decode(json_encode($options));

        $this->fs = new \tomk79\filesystem(array(
          'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
          'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
          'filesystem_encoding'     => define::FILESYSTEM_ENCODING
        ));

        $this->common = new common($this);
    }

    /**
     * Gitブランチのコミットハッシュ値を取得
     */
    public function get_commit_hash() {

        $commit_hash;

        $data = array(
                    'commit_hash' => ''
                );

        $current_dir = realpath('.');

        if (isset($this->options->branch_name) && isset($this->options->workdir_relativepath)) {

            // masterディレクトリの絶対パス
            $master_real_path = $this->fs->normalize_path($this->fs->get_realpath($this->options->workdir_relativepath . define::PATH_MASTER));

            if ( $master_real_path ) {

                if ( chdir( $master_real_path ) ) {

                    // コミットハッシュ値取得
                    $command = 'git log --pretty=%h ' . $this->options->branch_name . ' -1';
                    $ret = $this->common->command_execute($command, false);
                    foreach ( (array)$ret['output'] as $element ) {
                        $commit_hash = $element;
                    }

                } else {

                    // ディレクトリ移動に失敗
                    throw new \Exception('Failed to get git commitHash. Move to work directory failed.');
                } 
            }
        } else {

            // ディレクトリ移動に失敗
            throw new \Exception('Parameter is empty.');
        } 
        
        if ($commit_hash) {
            $data['commit_hash'] = $commit_hash;
        }
        
        chdir($current_dir);

        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data);
    }

}
