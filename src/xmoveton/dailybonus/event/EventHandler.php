<?php

namespace xmoveton\dailybonus\event;

use xmoveton\dailybonus\DailyBonus;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class EventHandler implements Listener {

    /**
     * @var DailyBonus
     */
    private $plugin;

    public function __construct (DailyBonus $plugin) {
        $this->plugin = $plugin;
    }

    public function playerJoin (PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        
        if ($user = $this->getPlugin()->hasAccount($player->getName())) {
            switch ($this->getPlugin()->time($user['last_date'])) {
                case 'сегодня':
                    //
                    break;
                case 'вчера':
                    $this->getPlugin()->updateAccount($player->getName(), "ADD_DAY");

                    $amount = ($this->getPlugin()->getAmount() * $user['amount_days']);
                    
                    $player->sendMessage($this->getPlugin()->getMessage("got", [$user['amount_days'], $amount]));
                    $this->getPlugin()->economy->addMoney($player->getName(), $amount);
                    break;
                case 'давно':
                    $player->sendMessage($this->getPlugin()->getMessage("pass", [$user['amount_days'], "%DAILY_PRIZE%"]));

                    $this->getPlugin()->updateAccount($player->getName(), "CLEAR_ACCOUNT");
                    break;
            }
        }
        else {
            $this->getPlugin()->createAccount($player->getName());
        }
    }

    /**
     * @return DailyBonus
     */
    private function getPlugin () {
        return $this->plugin;
    }
}