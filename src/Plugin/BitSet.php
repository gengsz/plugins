<?php
namespace Gengsz\Plugins\Plugin;

class BitSet {
    private string $bitstr;
    private bool $autoExpand;   // 是否允许自动扩展字节数组

    public function __construct(int $size = 128, bool $autoExpand = true) {
        $this->bitstr = str_repeat("\x00", (int)ceil($size / 8));
        $this->autoExpand = $autoExpand;
    }

    public function fromBin(string $bitstr): self {
        $this->bitstr = $bitstr;
        return $this;
    }

    public function toBin(): string {
        return $this->bitstr;
    }

    public function fromHex(string $hex): self {
        $this->bitstr = hex2bin($hex) ?: '';
        return $this;
    }

    public function toHex(): string {
        return bin2hex($this->bitstr);
    }

    public function set(int $index): void {
        $byte = intdiv($index, 8);
        $bit  = $index % 8;

        if (!isset($this->bitstr[$byte])) {
            if ($this->autoExpand) {
                $this->expandTo($byte + 1);
            } else {
                throw new \OutOfRangeException("Index {$index} 超出范围，且未启用自动扩展");
            }
        }

        $this->bitstr[$byte] = ($this->bitstr[$byte] ?? "\x00") | chr(1 << $bit);
    }

    public function clear(int $index): void {
        $byte = intdiv($index, 8);
        $bit  = $index % 8;

        if (!isset($this->bitstr[$byte])) {
            if ($this->autoExpand) {
                $this->expandTo($byte + 1);
            } else {
                return; // 不扩展时，超范围清空就忽略
            }
        }

        $this->bitstr[$byte] = $this->bitstr[$byte] & ~chr(1 << $bit);
    }

    public function has(int $index): bool {
        $byte = intdiv($index, 8);
        $bit  = $index % 8;
        return isset($this->bitstr[$byte]) && (((ord($this->bitstr[$byte]) >> $bit) & 1) === 1);
    }

    public function getAll(): array {
        $result = [];
        $length = strlen($this->bitstr);
        for ($byte = 0; $byte < $length; $byte++) {
            $byteVal = ord($this->bitstr[$byte]);
            if ($byteVal === 0) continue;
            for ($bit = 0; $bit < 8; $bit++) {
                if (($byteVal >> $bit) & 1) {
                    $result[] = $byte * 8 + $bit;
                }
            }
        }
        return $result;
    }

    public function debug(string $label = ''): void {
        if ($label) echo "== $label ==\n";
        echo "已点亮索引: " . implode(', ', $this->getAll()) . PHP_EOL;
        echo "Hex编码:    " . $this->toHex() . PHP_EOL;
        echo "长度:       " . strlen($this->bitstr) . " 字节" . PHP_EOL;
    }

    /** 内部扩展字节串到指定长度（字节数） */
    private function expandTo(int $bytes): void {
        $cur = strlen($this->bitstr);
        if ($cur < $bytes) {
            $this->bitstr .= str_repeat("\x00", $bytes - $cur);
        }
    }
}
