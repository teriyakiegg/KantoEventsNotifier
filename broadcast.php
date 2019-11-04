<?php

require_once __DIR__ . '/linebot.php';
require_once 'phpQuery-onefile.php';

$bot = new LineBotClass(false);

$bot->add_flex_builder("今日のホットなイベントだよ", createEventSearchMessage());

$bot->broadcast();


function createEventSearchMessage() {
    $flex_bubble = array();
    $event_list = getEventList();
    foreach ($event_list as $item) {
        $flex_bubble[] = createFlexMessage($item);
    }
    return $flex_bubble;
}


function getEventList() {
    $html = file_get_contents('https://www.walkerplus.com/event_list/ar0300/');
    $doc = phpQuery::newDocument($html);
    $list = $doc[".m-mainlist-item"];
  return $list;
}



function createFlexMessage($item) {
    $title = pq($item)->find('a:eq(0)')->text();
    $decs = pq($item)->find('a:eq(1)')->text();
    $place = pq($item)->find('a:eq(4)')->text();
    $img = pq($item)->find('img')->attr('src');
    $link = pq($item)->find('a:eq(0)')->attr('href');

    global $bot;
    $flex_box_mein = array();
    $flex_components = array();

    $flex_components['header'][] = $bot->create_text_component($place,array("size"=>5,"weight"=>"bold","color"=>"#e60033"));
    $flex_box_mein['header'] = $bot->create_box_component("vertical",$flex_components['header'],array("spacing"=>4));

    $flex_components['body'][] = $bot->create_text_component(trim($title),array("size"=>5,"weight"=>"bold","wrap"=>true));
    $flex_components['body'][] = $bot->create_text_component($decs,array("size"=>4,"wrap"=>true));
    $flex_box_mein['body'] = $bot->create_box_component("vertical",$flex_components['body'],array("spacing"=>3));

    $action = $bot->create_url_action_builder("イベント詳細をみる",'https://www.walkerplus.com'.$link);
    $flex_components['footer'][] = $bot->create_button_component($action,array("style"=>"secondary"));
    $flex_box_mein['footer'] = $bot->create_box_component("vertical",$flex_components['footer'],array("spacing"=>3));

    $image_url = 'https:'.$img;
    $bubble_blocks = array(
         "header" => $flex_box_mein['header']
        ,"hero" => $bot->create_image_component($image_url, array("size"=>11,"aspectRatio"=>"4:3","aspectMode"=>"cover"))
        ,"body" => $flex_box_mein['body']
        ,"footer" => $flex_box_mein['footer']
    );
    return $bot->create_bubble_container($bubble_blocks);
}

?>
