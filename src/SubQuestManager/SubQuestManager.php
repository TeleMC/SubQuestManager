<?PHP

namespace SubQuestManager;

use AbilityManager\AbilityManager;
use Core\Core;
use Core\util\Util;
use Equipments\Equipments;
use EtcItem\EtcItem;
use Monster\Monster;
use PacketEventManager\PacketEventManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use PrefixManager\PrefixManager;
use QuestManager\QuestManager;
use TeleMoney\TeleMoney;
use TutorialManager\TutorialManager;
use UiLibrary\UiLibrary;

class SubQuestManager extends PluginBase {

    private static $instance = null;
    public $pre = "§e•";

    //public $pre = "§l§e[ §f퀘스트 §e]§r§e";

    public static function getInstance() {
        return self::$instance;
    }

    public function onLoad() {
        self::$instance = $this;
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder());
        $this->saveResource("Quests.yml");
        $this->saveResource("QuestInfo.yml");
        $this->qdata = (new Config($this->getDataFolder() . "Quests.yml", Config::YAML))->getAll();
        $this->qidata = (new Config($this->getDataFolder() . "QuestInfo.yml", Config::YAML))->getAll();
        $this->user = new Config($this->getDataFolder() . "user.yml", Config::YAML);
        $this->udata = $this->user->getAll();
        $this->money = TeleMoney::getInstance();
        $this->core = Core::getInstance();
        $this->util = new Util($this->core);
        $this->equipments = Equipments::getInstance();
        $this->etcitem = EtcItem::getInstance();
        $this->packet = PacketEventManager::getInstance();
        $this->ui = UiLibrary::getInstance();
        $this->prefix = PrefixManager::getInstance();
        $this->monster = Monster::getInstance();
        $this->tutorial = TutorialManager::getInstance();
        $this->ability = AbilityManager::getInstance();
        $this->quest = QuestManager::getInstance();
    }

    public function onDisable() {
        $this->save();
    }

    public function save() {
        $this->user->setAll($this->udata);
        $this->user->save();
    }

    public function isQuest(string $name, string $quest): bool {
        return isset($this->udata[$name]["클리어"][$quest]);
    }

    public function Quest(Player $player, string $quest) {
        if ($quest == "밀리스") $this->Milis($player);
        else {
            $player->sendMessage("{$this->pre} 해당 퀘스트를 찾을 수 없습니다.");
            $player->sendMessage("{$this->pre} 설정된 퀘스트 : {$quest}");
            return;
        }
        return;
    }


    ///////////////////////////////////////////////////////// 토마스 /////////////////////////////////////////////////////////


    public function Milis(Player $player) {
        if (isset($this->plugin->quest->udata[$player->getName()]["퀘스트 듣는중..."])) return;
        else //////////////////// 진행중인 퀘스트 판단, 중도포기 ////////////////////

            if (in_array("밀리스", $this->udata[$player->getName()]["퀘스트 진행중..."])) {
                if (!isset($this->monster->idata[$player->getName()]["돼지고기"]) || $this->monster->idata[$player->getName()]["돼지고기"] < 20) {
                    $this->giveupQuest($player, "밀리스");
                } else {
                    $this->monster->idata[$player->getName()]["돼지고기"] -= 20;
                    $this->clearQuest($player, "밀리스");
                }
            } else

                //////////////////// 퀘스트 지급 ////////////////////

                $this->startQuest($player, "밀리스");
    }

    public function giveupQuest(Player $player, string $questName) {
        $this->Z[$player->getName()] = $questName;
        $form = $this->ui->ModalForm(function (Player $player, array $data) {
            $questName = $this->Z[$player->getName()];
            unset($this->Z[$player->getName()]);
            if ($data[0] == true) {
                unset($this->udata[$player->getName()]["퀘스트 진행중..."][array_search($questName, $this->udata[$player->getName()]["퀘스트 진행중..."])]);
                $player->sendMessage("{$this->pre} {$questName}의 부탁을 포기하였습니다.");
            } else {
                return false;
            }
        });
        $form->setTitle($this->qidata[$questName][0]);
        $form->setContent($this->getMessage_1($questName));
        $form->setButton1("§l[중도포기]");
        $form->setButton2("§l[진행]");
        $form->sendToPlayer($player);
    }

    private function getMessage_1(string $questName) {
        if (isset($this->qdata[$questName]["포기"])) {
            $text = "";
            foreach ($this->qdata[$questName]["포기"] as $key => $value) {
                $text .= $value . "\n";
            }
            //$text .= "\n";
            return $text;
        }
        return null;
    }

    public function clearQuest(Player $player, string $questName) {
        foreach ($this->qdata[$questName]["완료"] as $key => $value) {
            if ($key == "경험치") $this->util->addExp($player->getName(), $value);
            if ($key == "테나") $this->money->addMoney($player->getName(), $value);
        }
        unset($this->udata[$player->getName()]["퀘스트 진행중..."][array_search($questName, $this->udata[$player->getName()]["퀘스트 진행중..."])]);
        $form = $this->ui->SimpleForm(function (Player $player, array $data) {
        });
        $form->setTitle($this->qidata[$questName][0]);
        $form->setContent($this->getMessage_2($questName));
        $form->addButton("§l[확인]");
        $form->sendToPlayer($player);
    }

    private function getMessage_2(string $questName) {
        if (isset($this->qdata[$questName]["완료"]["메세지"])) {
            $text = "\n";
            foreach ($this->qdata[$questName]["완료"]["메세지"] as $key => $value) {
                $text .= $value . "\n";
            }
            $text .= "\n";
            return $text;
        }
        return null;
    }

    public function startQuest(Player $player, string $questName) {
        $this->Z[$player->getName()] = $questName;
        $form = $this->ui->ModalForm(function (Player $player, array $data) {
            $questName = $this->Z[$player->getName()];
            unset($this->Z[$player->getName()]);
            if ($data[0] == true) {
                $this->udata[$player->getName()]["퀘스트 진행중..."][] = $questName;
                $player->sendMessage("{$this->pre} {$questName}의 부탁을 수락하였습니다.");
            } else {
                $player->sendMessage("{$this->pre} {$questName}의 부탁을 거절하였습니다.");
            }
        });
        $form->setTitle($this->qidata[$questName][0]);
        $form->setContent($this->getMessage($questName));
        $form->setButton1("§l[수락]");
        $form->setButton2("§l[거절]");
        $form->sendToPlayer($player);
    }

    private function getMessage(string $questName) {
        if (isset($this->qdata[$questName]["대사"])) {
            $text = "";
            foreach ($this->qdata[$questName]["대사"] as $key => $value) {
                $text .= $value . "\n";
            }
            $text .= $this->qdata[$questName]["보상"];
            return $text;
        }
        return null;
    }
}
