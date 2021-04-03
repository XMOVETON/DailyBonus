<?php

namespace xmoveton\dailybonus\cmd;

use xmoveton\dailybonus\DailyBonus;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class DailybonusCommand extends Command {

    /**
     * @var DailyBonus
     */
    private $plugin;

    public function __construct (DailyBonus $plugin, $cmd) {
        parent::__construct($cmd);
        $this->plugin = $plugin;
    }

    public function execute (CommandSender $sender, $alias, array $params) {
        if (!($sender instanceof Player)) {
            $sender->sendMessage("Only for players");
            return true;
        }

        if ($row = $this->getPlugin()->hasAccount($sender->getName())) {
            $message = $this->getPlugin()->getMessage("dailybonus", [$row['amount_days'], "%DAILY_PRIZE%"]);
            $sender->sendMessage($message);
            return true;
        }
        $this->getPlugin()->getLogger()->error("У игрока {$sender->getName()} не создан аккаунт!");
    }

    /**
     * @return DailyBonus
     */
    private function getPlugin () {
        return $this->plugin;
    }
}