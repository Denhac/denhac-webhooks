<?php

namespace App\External\Slack;


use App\External\Slack\BlockActions\BlockActionStatic;
use App\External\Slack\Events\EventInterface;
use App\External\Slack\Modals\ModalInterface;
use App\External\Slack\Modals\ModalTrait;
use App\External\Slack\Shortcuts\ShortcutInterface;
use Illuminate\Support\Collection;
use ReflectionClass;

class ClassFinder
{
    private static function getReflectionClasses($namespace): Collection
    {
        return collect(get_declared_classes())
            ->filter(fn($name) => str_starts_with($name, $namespace))
            ->map(fn($name) => new ReflectionClass($name));
    }

    public static function getModal($callbackId)
    {
        return self::getReflectionClasses('App\\External\\Slack\\Modals')
            ->filter(fn($reflect) => $reflect->implementsInterface(ModalInterface::class))
            ->filter(fn($reflect) => array_key_exists(ModalTrait::class, $reflect->getTraits()))
            ->filter(fn($reflect) => $reflect->getMethod('callbackId')->invoke(null) == $callbackId)
            ->map(fn($reflect) => $reflect->getName())
            ->first();
    }

    public static function getEvent($eventType)
    {
        return self::getReflectionClasses('App\\External\\Slack\\Events')
            ->filter(fn($reflect) => $reflect->implementsInterface(EventInterface::class))
            ->filter(fn($reflect) => $reflect->getMethod('eventType')->invoke(null) == $eventType)
            ->map(fn($reflect) => $reflect->getName())
            ->first();
    }

    public static function getShortcut($callbackId)
    {
        return self::getReflectionClasses('App\\External\\Slack\\Shortcuts')
            ->filter(fn($reflect) => $reflect->implementsInterface(ShortcutInterface::class))
            ->filter(fn($reflect) => $reflect->getMethod('callbackId')->invoke(null) == $callbackId)
            ->map(fn($reflect) => $reflect->getName())
            ->first();
    }

    public static function getBlockAction($blockId, $actionId)
    {
        return self::getReflectionClasses('App\\External\\Slack\\BlockActions')
            ->filter(fn($reflect) => $reflect->implementsInterface(BlockActionStatic::class))
            ->filter(fn($reflect) => $reflect->getMethod('blockId')->invoke(null) == $blockId)
            ->filter(fn($reflect) => $reflect->getMethod('actionId')->invoke(null) == $actionId)
            ->map(fn($reflect) => $reflect->getName())
            ->first();
    }

}
