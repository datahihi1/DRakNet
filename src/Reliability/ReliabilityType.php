<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Reliability;

/**
 * Các loại Reliability trong RakNet
 * 
 * Theo tài liệu RakNet Protocol:
 * - ID: 3 bits (0-7)
 * - Mỗi loại có các thuộc tính: IsReliable, IsOrdered, IsSequenced
 */
final class ReliabilityType
{
    /** Unreliable - Không đảm bảo gửi đến */
    public const UNRELIABLE = 0;
    
    /** UnreliableSequenced - Có thứ tự nhưng không đảm bảo */
    public const UNRELIABLE_SEQUENCED = 1;
    
    /** Reliable - Đảm bảo gửi đến */
    public const RELIABLE = 2;
    
    /** ReliableOrdered - Đảm bảo và có thứ tự */
    public const RELIABLE_ORDERED = 3;
    
    /** ReliableSequenced - Đảm bảo và có thứ tự sequenced */
    public const RELIABLE_SEQUENCED = 4;
    
    /** UnreliableWithAckReceipt - Không đảm bảo nhưng có ACK receipt */
    public const UNRELIABLE_WITH_ACK_RECEIPT = 5;
    
    /** ReliableWithAckReceipt - Đảm bảo và có ACK receipt */
    public const RELIABLE_WITH_ACK_RECEIPT = 6;
    
    /** ReliableOrderedWithAckReceipt - Đảm bảo, có thứ tự và ACK receipt */
    public const RELIABLE_ORDERED_WITH_ACK_RECEIPT = 7;
    
    /**
     * Kiểm tra xem reliability type có đảm bảo (reliable) không
     * 
     * @param int $reliability
     * @return bool
     */
    public static function isReliable(int $reliability): bool
    {
        return in_array($reliability, [
            self::RELIABLE,
            self::RELIABLE_ORDERED,
            self::RELIABLE_SEQUENCED,
            self::RELIABLE_WITH_ACK_RECEIPT,
            self::RELIABLE_ORDERED_WITH_ACK_RECEIPT,
        ]);
    }
    
    /**
     * Kiểm tra xem reliability type có sequenced không
     * 
     * @param int $reliability
     * @return bool
     */
    public static function isSequenced(int $reliability): bool
    {
        return in_array($reliability, [
            self::UNRELIABLE_SEQUENCED,
            self::RELIABLE_SEQUENCED,
        ]);
    }
    
    /**
     * Kiểm tra xem reliability type có ordered không
     * 
     * @param int $reliability
     * @return bool
     */
    public static function isOrdered(int $reliability): bool
    {
        return in_array($reliability, [
            self::RELIABLE_ORDERED,
            self::RELIABLE_SEQUENCED,
            self::RELIABLE_ORDERED_WITH_ACK_RECEIPT,
        ]);
    }
    
    /**
     * Kiểm tra xem reliability type có ACK receipt không
     * 
     * @param int $reliability
     * @return bool
     */
    public static function hasAckReceipt(int $reliability): bool
    {
        return in_array($reliability, [
            self::UNRELIABLE_WITH_ACK_RECEIPT,
            self::RELIABLE_WITH_ACK_RECEIPT,
            self::RELIABLE_ORDERED_WITH_ACK_RECEIPT,
        ]);
    }
    
    /**
     * Kiểm tra xem reliability type có hợp lệ không
     * 
     * @param int $reliability
     * @return bool
     */
    public static function isValid(int $reliability): bool
    {
        return $reliability >= 0 && $reliability <= 7;
    }
    
    /**
     * Lấy tên của reliability type
     * 
     * @param int $reliability
     * @return string
     */
    public static function getName(int $reliability): string
    {
        return match($reliability) {
            self::UNRELIABLE => 'Unreliable',
            self::UNRELIABLE_SEQUENCED => 'UnreliableSequenced',
            self::RELIABLE => 'Reliable',
            self::RELIABLE_ORDERED => 'ReliableOrdered',
            self::RELIABLE_SEQUENCED => 'ReliableSequenced',
            self::UNRELIABLE_WITH_ACK_RECEIPT => 'UnreliableWithAckReceipt',
            self::RELIABLE_WITH_ACK_RECEIPT => 'ReliableWithAckReceipt',
            self::RELIABLE_ORDERED_WITH_ACK_RECEIPT => 'ReliableOrderedWithAckReceipt',
            default => 'Unknown',
        };
    }
}

