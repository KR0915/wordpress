<?php
/*
Plugin Name: Vulnerable Plugin
Description: 学習用に意図的な XSS 脆弱性を持たせたテストプラグイン
Version: 1.0
Author: Your Name
*/

// 管理画面に「Vuln」メニューを追加
add_action('admin_menu', function(){
    // add_menu_page(ページタイトル, メニューラベル, 必要権限, スラッグ, コールバック関数)
    add_menu_page('Vuln', 'Vuln', 'manage_options', 'vuln', 'vuln_page');
});

function vuln_page(){
    // Reflected XSS 用：URL パラメータ msg をエスケープせずに直接出力
    // (本来は esc_html() 等で必ずエスケープすべき)
    echo '<h1>' . ($_GET['msg'] ?? '') . '</h1>';

    // フォーム送信された comment が存在する場合
    if ( ! empty( $_POST['comment'] ) ) {
        // WordPress が自動付加するバックスラッシュを削除
        $raw_comment = wp_unslash( $_POST['comment'] );
        // Stored XSS 用：update_option で常に上書き保存
        update_option( 'vuln_comment', $raw_comment );
    }

    // コメント投稿フォーム
    echo '<form method="post">
            <input name="comment" type="text" placeholder="コメントを入力" />
            <button type="submit">投稿</button>
          </form>';

    // Stored XSS 用：データベースから取得したままを出力
    // (本来は esc_html() などでエスケープすべき)
    echo '<div>' . get_option( 'vuln_comment' ) . '</div>';
}
