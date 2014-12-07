<?php
namespace Model;

use Pagon\Url;

/**
 * User Model
 *
 * @package Model
 *
 * @param string $username 用户名
 * @param string $password 密码
 * @param string $url      外链
 * @param int    $status   状态    0:待审 1:正常 -1:删除
 */
class User extends Model
{
    public static $_table = 'users';

    // Default status
    public $status = self::UNCHECKED;

    /**
     * Define constant
     */
    const DELETED = -1;
    const UNCHECKED = 0;
    const OK = 1;

    /**
     * Get permalink
     *
     * @param bool $full
     * @return string
     */
    public function permalink($full = false)
    {
        return Url::to('/posts/' . $this->id, null, $full);
    }

    /**
     * Is status deleted?
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->status == self::DELETED;
    }

    /**
     * Is status ok?
     *
     * @return bool
     */
    public function isOK()
    {
        return $this->status == self::OK;
    }

    /**
     * Is status unchecked?
     *
     * @return bool
     */
    public function isUnChecked()
    {
        return $this->status == self::UNCHECKED;
    }
}