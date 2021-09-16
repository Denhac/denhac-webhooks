<?php

namespace App\Slack\BlockActions;


use App\Http\Requests\SlackRequest;

trait RespondsToBlockActions
{
    /**
     * @return BlockActionInterface[]
     */
    public abstract static function getBlockActions(): array;

    static abstract function onBlockAction(SlackRequest $request);

    protected static function blockActionUpdate($blockId, $actionId = null): BlockActionInterface
    {
        if(is_null($actionId)) {
            $actionId = $blockId;
        }

        return new class(static::class, $blockId, $actionId) implements BlockActionInterface {
            private string $className;
            private string $blockId;
            private string $actionId;

            public function __construct($className, $blockId, $actionId)
            {
                $this->className = $className;
                $this->blockId = $blockId;
                $this->actionId = $actionId;
            }

            public function blockId(): string
            {
                return $this->blockId;
            }

            public function actionId(): string
            {
                return $this->actionId;
            }

            public function handle(SlackRequest $request)
            {
                $r = new \ReflectionClass($this->className);

                /** @var RespondsToBlockActions $instance */
                $instance =  $r->newInstanceWithoutConstructor();

                return $instance::onBlockAction($request);
            }
        };
    }
}
