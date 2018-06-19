<?php
namespace cat;

// 序列化整个本地调用链
class Codec
{
    const VERSION = 'PT1';
    const THREAD_GROUP_NAME = 'PHP';
    const THREAD_NAME = "PHP";
    const SESSION_TOKEN = '';

    const WITH_DEFAULT = 0;
    const WITHOUT_STATUS = 1;
    const WITH_DURATION = 2;

    const TIME_FORMAT = 'Y-m-d H:i:s';

    const TAB = "\t";
    const LF = "\n";

    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    private function formatTimestamp($timestamp)
    {
        $timestamp = intval($timestamp / 1000); // 毫秒
        $date = date(self::TIME_FORMAT, intval($timestamp / 1000));
        return sprintf("%s.%03d", $date, $timestamp % 1000);
    }

    private function encodeHeader()
    {
        $context = $this->manager->getServerContext();
        $fields = [
            self::VERSION,
            $context->domain,
            $context->hostname,
            $context->ip,
            self::THREAD_GROUP_NAME,
            getmypid(),
            self::THREAD_NAME,
            $context->catChildMessageId,     // message id
            $context->catParentMessageId,   // parent id
            $context->catRootMessageId,     // root id
            self::SESSION_TOKEN,    // ''
        ];
        return implode(self::TAB, $fields) . self::LF;
    }

    private function encodeLine($msgType, $message, $policy)
    {
        $fields = [];

        if ($msgType == 'T') {
            $fields[] = $msgType . $this->formatTimestamp($message->timestamp + $message->duration);
        } else {
            $fields[] = $msgType . $this->formatTimestamp($message->timestamp);
        }

        $fields[] = $message->type;
        $fields[] = $message->name;

        if ($policy != self::WITHOUT_STATUS) {
            $fields[] = $message->status;

            if ($policy == self::WITH_DURATION) {
                $fields[] = $message->duration . 'us';
            }

            $fields[] = is_array($message->data) ? json_encode($message->data) : $message->data;
        }

        $fields = implode(self::TAB, $fields);
        return $fields . self::TAB . self::LF;
    }

    private function encodeMessage($message)
    {
        if (!$message->isCompleted()) {
            // XX: SDK应该保证不出现这种情况
        }

        $lines = [];

        if ($message instanceof Transaction) {
            // 原子transaction
            if (!count($message->children)) {
                $lines[] = $this->encodeLine('A', $message, self::WITH_DURATION);
            } else {
                $lines[] = $this->encodeLine('t', $message, self::WITHOUT_STATUS);
                foreach ($message->children as $child) {
                    $lines[] = $this->encodeMessage($child);
                }
                $lines[] = $this->encodeLine('T', $message, self::WITH_DURATION);
            }
        } else if ($message instanceof Event) {
            $lines[] = $this->encodeLine('E', $message, self::WITH_DEFAULT);
        } else if ($message instanceof Metric) {
            $lines[] = $this->encodeLine('M', $message, self::WITH_DEFAULT);
        }

        return implode('', $lines);
    }

    // 编码本地的调用树
    public function encode($rootTran)
    {
        $catData = $this->encodeHeader() . $this->encodeMessage($rootTran);
        return pack('N', strlen($catData)) . $catData;
    }
}