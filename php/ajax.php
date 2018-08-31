<?php

namespace indigo;

/**
 * コミットハッシュ値ajax取得クラス。
 *
 * 入力ダイアログのブランチに紐づくコミットハッシュ値をGitを介して取得するクラス。
 *
 */
class ajax
{
    public $options;

    /**
     * オブジェクト
     * @access private
     */
    private $fs, $common;
    
    private $user_id;

    /**
     * コンストラクタ
     *
     * @param array $options パラメタ情報
     */
    public function __construct($options) {

        $this->put_ajax_log("__construct call");

        $this->options = \json_decode(json_encode($options));

        $this->fs = new \tomk79\filesystem(array(
          'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
          'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
          'filesystem_encoding'     => define::FILESYSTEM_ENCODING
        ));

        // ログファイル名
        $log_dirname = gmdate("Ymd", time());

        // ログパス
        $this->ajax_log_path = $this->fs->normalize_path($this->fs->get_realpath($this->options->realpath_workdir . define::PATH_LOG)) . 'log_ajax_' . $log_dirname . '.log';

        //============================================================
        // オプションの任意項目
        //============================================================
        if (\array_key_exists('user_id', $this->options)) {
            $this->user_id = $this->options->user_id;
        }
    }

    /**
     * Gitブランチのコミットハッシュ値を取得
     *
     * @return json json_encode($ret) コミットハッシュ値(json変換)
     * 
     * @throws Exception ブランチ名、作業ディレクトリ名パラメタがGETから取得できなかった場合
     * @throws Exception masterブランチディレクトリへの移動に失敗した場合
     */
    public function get_commit_hash() {

        $commit_hash;

        $ret = array(
                    'commit_hash' => ''
                );

        $current_dir = realpath('.');

        if (isset($this->options->_GET->branch_name) && isset($this->options->_GET->realpath_workdir)) {

            // masterディレクトリの絶対パス
            $master_real_path = $this->fs->normalize_path($this->fs->get_realpath($this->options->_GET->realpath_workdir . define::PATH_MASTER));

            if ( $master_real_path ) {

                if ( \chdir( $master_real_path ) ) {

                    // コミットハッシュ値取得
                    $command = 'git log --pretty=%h ' . define::GIT_REMOTE_NAME . '/' . $this->options->_GET->branch_name . ' -1';
                     
                     $this->put_ajax_log($command);

                    \exec($command, $output, $return);
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
        
        \chdir($current_dir);

        \header('Content-Type: application/json; charset=utf-8');

        $this->put_ajax_log("【commit hash】 " . $commit_hash);

        return \json_encode($ret);
    }

    /**
     * ajax用のログ書き込み
     *
     * @param string $text 出力文字列
     * 
     * @return 成功した場合に TRUE を、失敗した場合に FALSEを返却
     */
    private function put_ajax_log($text){
        
        $datetime = \gmdate("Y-m-d H:i:s", \time());

        $str = "[" . $datetime . "]" . " " .
               "[pid:" . \getmypid() . "]" . " " .
               "[userid:" . $this->user_id . "]" . " " .
               "[" . __METHOD__ . "]" . " " .
               "[line:" . __LINE__ . "]" . " " .
               $text . "\r\n";

        return \error_log( $str, 3, $this->ajax_log_path );
    }
}
