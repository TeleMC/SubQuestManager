<?php

namespace SubQuestManager;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Server;

class EventListener implements Listener {

    public function __construct(SubQuestManager $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $ev) {
        $player = $ev->getPlayer();
        if (!isset($this->plugin->udata[$player->getName()]["퀘스트 진행중..."]))
            $this->plugin->udata[$player->getName()]["퀘스트 진행중..."] = [];
    }

}
