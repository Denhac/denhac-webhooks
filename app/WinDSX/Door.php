<?php


namespace App\WinDSX;


use Illuminate\Support\Collection;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class Door
{
    public bool $shouldOpen;

    private function __construct(
        public string $humanReadableName,
        public int $dsxDeviceId, // The specific hardware ID based on what 1042 card this door is wired to.
        public int $dsxRelayBoard, // The relay board ID in the stack attached to the Raspberry Pi
        public int $dsxRelayId, // The specific relay number on that board.
        public bool $openDuringOpenHouseByDefault,
        public bool $membersCanBadgeIn
    ) {
        $this->shouldOpen = $this->openDuringOpenHouseByDefault;
    }

    public function shouldOpen(bool $shouldOpen): static
    {
        $this->shouldOpen = $shouldOpen;

        return $this;
    }

    #[ArrayShape(["device" => "int", "board" => "int", "relay" => "int", "open" => "bool"])]
    public function toRelay(): array {
        return [
            "device" => $this->dsxDeviceId,
            "board" => $this->dsxRelayBoard,
            "relay" => $this->dsxRelayId,
            "open" => $this->shouldOpen,
        ];
    }

    public static function all(): Collection {
        return collect([
            self::glassWorkshopDoor(),
            self::kitchenWorkshopDoor(),
            self::studioBDoor(),
            self::KGNUDoor(),
            self::dirtyRoomDoor(),
            self::kitchenGlassDoor(),
            self::glassDoubleDoors(),
        ]);
    }

    public static function byDSXDeviceId(int $dsxDeviceId): ?Door {
        return self::all()
            ->first(function($door) use ($dsxDeviceId) {
                /** @var $door Door */
                return $door->dsxDeviceId == $dsxDeviceId;
            });
    }

    #[Pure] public static function glassWorkshopDoor(): Door
    {
        return new Door(
            humanReadableName: "Glass Workshop Door",
            dsxDeviceId: 3,
            dsxRelayBoard: 0,
            dsxRelayId: 1,
            openDuringOpenHouseByDefault: true,
            membersCanBadgeIn: true
        );
    }

    #[Pure] public static function kitchenWorkshopDoor(): Door
    {
        return new Door(
            humanReadableName: "Kitchen Workshop Door",
            dsxDeviceId: 8,
            dsxRelayBoard: 0,
            dsxRelayId: 7,
            openDuringOpenHouseByDefault: true,
            membersCanBadgeIn: true
        );
    }

    #[Pure] public static function studioBDoor(): Door
    {
        return new Door(
            humanReadableName: "Electronics/Lounge Door",
            dsxDeviceId: 9,
            dsxRelayBoard: 0,
            dsxRelayId: 8,
            openDuringOpenHouseByDefault: true,
            membersCanBadgeIn: true
        );
    }

    #[Pure] public static function KGNUDoor(): Door
    {
        return new Door(
            humanReadableName: "Craft/3D Room Door",
            dsxDeviceId: 10,
            dsxRelayBoard: 0,
            dsxRelayId: 4,
            openDuringOpenHouseByDefault: true,
            membersCanBadgeIn: true
        );
    }

    #[Pure] public static function dirtyRoomDoor(): Door
    {
        return new Door(
            humanReadableName: "Dirty Room Door",
            dsxDeviceId: 2,
            dsxRelayBoard: 0,
            dsxRelayId: 2,
            openDuringOpenHouseByDefault: false,
            membersCanBadgeIn: false
        );
    }

    #[Pure] public static function kitchenGlassDoor(): Door
    {
        return new Door(
            humanReadableName: "Kitchen Glass Door",
            dsxDeviceId: 1,
            dsxRelayBoard: 0,
            dsxRelayId: 5,
            openDuringOpenHouseByDefault: false,
            membersCanBadgeIn: true
        );
    }

    #[Pure] public static function glassDoubleDoors(): Door
    {
        return new Door(
            humanReadableName: "Glass Double Doors",
            dsxDeviceId: 0,
            dsxRelayBoard: 0,
            dsxRelayId: 6,
            openDuringOpenHouseByDefault: false,
            membersCanBadgeIn: true
        );
    }
}
