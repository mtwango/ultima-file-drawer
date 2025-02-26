<?php

namespace Ultima\Domain;

use InvalidArgumentException;
use Ultima\Domain\Race\Elf;
use Ultima\Domain\Race\Gargoyle;
use Ultima\Domain\Race\Human;

abstract class Race {

  /**
   * @param int $raceType
   *
   * @return Race
   */
  public static function forType(int $raceType): Race {
    switch ($raceType) {
      case RaceType::HUMAN:
        return Human::race();
      case RaceType::ELF:
        return Elf::race();
      case RaceType::GARGOYLE:
        return Gargoyle::race();
      default:
        throw new InvalidArgumentException('invalid race type ' . $raceType);
    }
  }

  /**
   * @return Race
   */
  public static function race(): self {
    return new static;
  }

  /**
   * @return int
   */
  abstract public function getType();

  /**
   * @param bool $female
   *
   * @return int
   */
  abstract public function getBodyId(bool $female): int;

}
