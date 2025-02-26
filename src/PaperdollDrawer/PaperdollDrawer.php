<?php

namespace Ultima\PaperdollDrawer;

use RuntimeException;
use Ultima\Muls\GumpArtReader;
use Ultima\Muls\GumpIndexReader;
use Ultima\Muls\HueReader;
use Ultima\Muls\TileDataReader;
use Ultima\PaperdollDrawer\Entry\BodyEntry;
use Ultima\PaperdollDrawer\Entry\GumpEntry;

class PaperdollDrawer {

  /** @var GumpIndexReader */
  private $gumpIndexReader;

  /** @var GumpArtReader */
  private $gumpArtReader;

  /** @var TileDataReader */
  private $tileDataReader;

  /** @var HueReader */
  private $hueReader;

  /**
   * This bool is used to move pixels to center character in paperdoll.
   */
  private bool $gumpPositioned = FALSE;

  public function __construct(
    GumpIndexReader $gumpIndexReader,
    GumpArtReader $gumpArtReader,
    TileDataReader $tileDataReader,
    HueReader $hueReader
  ) {
    $this->hueReader = $hueReader;
    $this->gumpIndexReader = $gumpIndexReader;
    $this->gumpArtReader = $gumpArtReader;
    $this->tileDataReader = $tileDataReader;
  }

  /**
   * @param string $mulPath
   *
   * @return PaperdollDrawer
   */
  public static function with(string $mulPath): self {
    $mulPath = rtrim($mulPath, '/');

    return new PaperdollDrawer(
      new GumpIndexReader("$mulPath/gumpidx.mul"),
      new GumpArtReader("$mulPath/gumpart.mul"),
      new TileDataReader("$mulPath/tiledata.mul"),
      new HueReader("$mulPath/hues.mul")
    );
  }

  /**
   * @param Paperdoll $paperdoll
   *
   * @return resource
   */
  public function drawPaperdoll(Paperdoll $paperdoll) {
    // Create empty image.
    $canvas = imagecreatetruecolor(262, 324);
    // Replace default black background with transparent color.
    imagesavealpha($canvas, TRUE);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);

    // Insert paperdoll background.
    $this->addEntry($canvas, new BodyEntry(0x7d1, 0, FALSE));

    $this->gumpPositioned = TRUE;

    // Insert character body.
    $this->addEntry($canvas, $paperdoll->getBodyEntry());

    // Add items.
    foreach ($paperdoll->getItemEntries() as $entry) {
      $this->addEntry($canvas, $entry);
    }

    // Add name and title.
    $this->addText($canvas, $paperdoll->getName(), 277);
    $this->addText($canvas, $paperdoll->getTitle(), 295);

    return $canvas;
  }

  private function addEntry($canvas, GumpEntry $entry) {
    $index = $entry->getIndex();
    if ($index > 0xFFFF || $index <= 0) {
      return;
    }

    // Male/Female Gumps or IsGump Param.
    if ($entry->isGump()) {
      $gumpId = $index;
      $this->loadRawGump($canvas, $gumpId, $entry->getHue());

      return;
    }

    $itemData = $this->tileDataReader->readItemData($index);

    if (!$itemData->isWearable()) {
      return;
    }

    $gumpId = $itemData->value();
    if ($gumpId < 10000) {
      if ($entry->isFemale()) {
        $gumpId += 60000;
      }
      else {
        $gumpId += 50000;
      }
    }

    $this->loadRawGump($canvas, $gumpId, $entry->getHue());
  }

  private function loadRawGump($canvas, int $index, int $hueId) {
    $hue = ($hueId > 0) ? $this->hueReader->readHue($hueId) : NULL;
    $gumpData = $this->gumpIndexReader->readGumpData($index);
    $data = $this->gumpArtReader->readGump($gumpData, $hue);

    foreach ($data as $datum) {
      $this->addGump($canvas, $datum);
    }
  }

  private function addGump($canvas, $datum) {
    $x = (int) $datum[0];
    $y = (int) $datum[1];
    $r = (int) $datum[2];
    $g = (int) $datum[3];
    $b = (int) $datum[4];
    $length = (int) $datum[5]; // pixel color repeat length

    // Center character in gump window.
    if ($this->gumpPositioned) {
      $x += 8;
      $y += 15;
    }

    if ($r || $g || $b) {
      $color = imagecolorallocate($canvas, $r, $g, $b);
      for ($i = 0; $i < $length; $i++) {
        imagesetpixel($canvas, $x + $i, $y, $color);
      }
    }
  }

  private function addText($canvas, $text, $y) {
    $textColor = imagecolorallocate($canvas, 25, 25, 25);
    $pos = 40;

    imagefttext($canvas, 10, 0, $pos, $y, $textColor, __DIR__ . '/../../fonts/uo-classic_v0.2.ttf', $text);
  }

}
