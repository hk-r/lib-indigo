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

        // ログファイル名
        $log_dirname = gmdate("Ymd", time());

        // ログパス
        $this->ajax_log_path = $this->fs->normalize_path($this->fs->get_realpath($this->options->workdir_relativepath . define::PATH_LOG)) . 'log_ajax_' . $log_dirname . '.log';
    }

    /**
     * Gitブランチのコミットハッシュ値を取得
     */
    public function get_commit_hash() {

        $this->put_ajax_log("■ get_commit_hash start");

        $commit_hash;

        $ret = array(
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
                    exec($command, $output, $return);
                    foreach ( (array)$output as $data ) {
                        $commit_hash = $data;
                    }

                } else {

                    $this->put_ajax_log("Error. Move to work directory failed.");

                    // ディレクトリ移動に失敗
                    throw new \Exception('Failed to get git commitHash.');
                } 
            }
        } else {

            $this->put_ajax_log("Error. Parameter not found.");

            // ディレクトリ移動に失敗
            throw new \Exception('Failed to get git commitHash.');
        } 
        
        if ($commit_hash) {
            $ret['commit_hash'] = $commit_hash;
        }
        
        chdir($current_dir);

        header('Content-Type: application/json; charset=utf-8');

        $this->put_ajax_log("■ get_commit_hash end");

        return json_encode($ret);
    }

    /**
     * response status code を取得する。
     *
     * `$px->set_status()` で登録した情報を取り出します。
     *
     * @return int ステータスコード (100〜599の間の数値)
     */
    private function put_ajax_log($text){
        
        $datetime = gmdate("Y-m-d H:i:s", time());

        $str = "[" . $datetime . "]" . " " .
               "[pid:" . getmypid() . "]" . " " .
               // "[userid:" . $this->main->options->user_id . "]" . " " .
               "[" . __METHOD__ . "]" . " " .
               "[line:" . __LINE__ . "]" . " " .
               $text . "\r\n";

        // file_put_contents($path, $str, FILE_APPEND);

        return error_log( $str, 3, $this->ajax_log_path );
    }
}
