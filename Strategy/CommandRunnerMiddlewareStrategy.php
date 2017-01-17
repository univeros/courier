<?php
namespace Altair\Courier\Strategy;

use Altair\Courier\Contracts\CommandMessageInterface;
use Altair\Courier\Contracts\CommandMiddlewareInterface;
use Altair\Courier\Contracts\CommandRunnerStrategyInterface;
use Altair\Courier\Exception\InvalidCommandMiddlewareException;
use Closure;

class CommandRunnerMiddlewareStrategy implements CommandRunnerStrategyInterface
{
    /**
     * @var array
     */
    protected $middlewares;

    /**
     * CommandRunnerMiddlewareStrategy constructor.
     *
     * @param array|null $middlewares
     */
    public function __construct(array $middlewares = null)
    {
        $this->middlewares = $middlewares?? [];
    }

    /**
     * Returns a new instance with
     * @param array $middlewares
     *
     * @return CommandRunnerStrategyInterface
     * @throws InvalidCommandMiddlewareException
     */
    public function withMiddlewares(array $middlewares): CommandRunnerStrategyInterface
    {
        foreach ($middlewares as $middleware) {
            if (!is_subclass_of($middleware, CommandMiddlewareInterface::class)) {
                throw new InvalidCommandMiddlewareException(
                    sprintf(
                        'Invalid command middleware %s does not implement %s',
                        $middleware,
                        CommandMiddlewareInterface::class
                    )
                );
            }
        }

        return new static($middlewares);
    }

    /**
     * @param CommandMiddlewareInterface $middleware
     *
     * @return CommandRunnerStrategyInterface
     */
    public function add(CommandMiddlewareInterface $middleware): CommandRunnerStrategyInterface
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Initiates middleware sequence.
     *
     * @param CommandMessageInterface $message
     */
    public function run(CommandMessageInterface $message)
    {
        call_user_func($this->call(0), $message);
    }

    /**
     * Fire up middleware chain.
     *
     * @param int $index
     *
     * @return Closure
     */
    protected function call(int $index): Closure
    {
        if (!isset($this->middlewares[$index])) {
            return function () {
            };
        }
        $middleware = $this->middlewares[$index];

        return function ($message) use ($middleware, $index) {
            $middleware->handle($message, $this->call($index + 1));
        };
    }
}