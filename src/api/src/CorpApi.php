<?php
namespace src\api\src;

use src\utils\HttpUtils;
use src\utils\Utils;
use src\utils\error\ParameterError;

use src\api\src\Api;

use src\api\datastructure\message\Message;
use src\api\datastructure\user\User;

class CorpApi extends Api
{
    private $corpId = null;
    private $secret = null;
    protected $accessToken = null;

    /**
     * @brief __construct : 构造函数，
     * @note 企业进行自定义开发调用, 请传参 corpid + secret, 不用关心accesstoken，本类会自动获取并刷新
     */
    public function __construct($corpId=null, $secret=null)
    {
        Utils::checkNotEmptyStr($corpId, "corpid");
        Utils::checkNotEmptyStr($secret, "secret");

        $this->corpId = $corpId;
        $this->secret = $secret;
    }


    // ------------------------- access token ---------------------------------
    /**
     * @brief GetAccessToken : 获取 accesstoken，不用主动调用
     *
     * @return : string accessToken
     */
    protected function GetAccessToken()
    {
        if ( ! Utils::notEmptyStr($this->accessToken)) { 
            $this->RefreshAccessToken();
        } 
        return $this->accessToken;
    }

    protected function RefreshAccessToken()
    {
        if (!Utils::notEmptyStr($this->corpId) || !Utils::notEmptyStr($this->secret))
            throw new ParameterError("invalid corpid or secret");

        $url = HttpUtils::MakeUrl(
            "/cgi-bin/gettoken?corpid={$this->corpId}&corpsecret={$this->secret}");
        $this->_HttpGetParseToJson($url, false);
        $this->_CheckErrCode();

        $this->accessToken = $this->rspJson["access_token"];
    }
    //
    // --------------------------- 消息推送 -----------------------------------
    //
    //
    /**
     * @brief MessageSend : 发送消息
     *
     * @link https://work.weixin.qq.com/api/doc#10167
     *
     * @param $message : Message
     * @param $invalidUserIdList : string array
     * @param $invalidPartyIdList : uint array
     * @param $invalidTagIdList : uint array
     *
     * @return 
     */
    public function MessageSend(Message $message, &$invalidUserIdList, &$invalidPartyIdList, &$invalidTagIdList)
    {
        $message->CheckMessageSendArgs(); 
        $args = $message->Message2Array();

        self::_HttpCall(self::MESSAGE_SEND, 'POST', $args); 

        $invalidUserIdList_string = utils::arrayGet($this->rspJson, "invaliduser");
        $invalidUserIdList = explode('|', $invalidUserIdList_string);

        $invalidPartyIdList_string = utils::arrayGet($this->rspJson, "invalidparty");
        $temp = explode('|', $invalidPartyIdList_string);
        foreach($temp as $item) {
            $invalidPartyIdList[] = intval($item);
        }

        $invalidTagIdList_string = utils::arrayGet($this->rspJson, "invalidtag");
        $temp = explode('|', $invalidTagIdList_string);
        foreach($temp as $item) {
            $invalidTagIdList[] = intval($item);
        }
    }
    
    // ------------------------- 成员管理 -------------------------------------
    //
    /**
     * @brief UserList : 获取部门成员详情
     *
     * @link https://work.weixin.qq.com/api/doc#10063
     *
     * @param $departmentId : uint
     * @param $fetchChild : 1/0 是否递归获取子部门下面的成员
     *
     * @return 
     */
    public function UserList($departmentId, $fetchChild)
    {
        Utils::checkIsUInt($departmentId, "departmentId");
        self::_HttpCall(self::USER_LIST, 'GET', array('department_id'=>$departmentId, 'fetch_child'=>$fetchChild)); 
        return User::Array2UserList($this->rspJson);
    }
}
