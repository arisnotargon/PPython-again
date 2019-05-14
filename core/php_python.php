<?php
//-----------------------------------------------------------
// PPython(PHP and Python).
//   (2012-15 http://code.google.com/p/ppython/)
//
// License: http://www.apache.org/licenses/LICENSE-2.0
//-----------------------------------------------------------

define("LAJP_IP", "127.0.0.1");     //Python��IP
define("LAJP_PORT", 10240);         //Python�������˿�

define("PARAM_TYPE_ERROR", 101);    //�������ʹ���
define("SOCKET_ERROR", 102);        //SOCKET����
define("LAJP_EXCEPTION", 104);      //Python�˷����쳣

function ppython()
{
    //��������
    $args_len = func_num_args();
    //��������
    $arg_array = func_get_args();

    //������������С��1
    if ($args_len < 1)
    {
        throw new Exception("[PPython Error] lapp_call function's arguments length < 1", PARAM_TYPE_ERROR);
    }
    //��һ��������Pythonģ�麯�����ƣ�������string����
    if (!is_string($arg_array[0]))
    {
        throw new Exception("[PPython Error] lapp_call function's first argument must be string \"module_name::function_name\".", PARAM_TYPE_ERROR);
    }


    if (($socket = socket_create(AF_INET, SOCK_STREAM, 0)) === false)
    {
        throw new Exception("[PPython Error] socket create error.", SOCKET_ERROR);
    }

    if (socket_connect($socket, LAJP_IP, LAJP_PORT) === false)
    {
        throw new Exception("[PPython Error] socket connect error.", SOCKET_ERROR);
    }

    //��Ϣ�����л�
    $request = serialize($arg_array);
    $req_len = strlen($request);

    $request = $req_len.",".$request;

    //echo "{$request}<br>";

    $send_len = 0;
    do
    {
        //����
        if (($sends = socket_write($socket, $request, strlen($request))) === false)
        {
            throw new Exception("[PPython Error] socket write error.", SOCKET_ERROR);
        }

        $send_len += $sends;
        $request = substr($request, $sends);

    }while ($send_len < $req_len);

    //����
    $response = "";
    while(true)
    {
        $recv = "";
        if (($recv = socket_read($socket, 1400)) === false)
        {
            throw new Exception("[PPython Error] socket read error.", SOCKET_ERROR);
        }
        if ($recv == "")
        {
            break;
        }

        $response .= $recv;

        //echo "{$response}<br>";

    }

    //�ر�
    socket_close($socket);

    $rsp_stat = substr($response, 0, 1);    //�������� "S":�ɹ� "F":�쳣
    $rsp_msg = substr($response, 1);        //������Ϣ

    //echo "��������:{$rsp_stat},������Ϣ:{$rsp_msg}<br>";

    if ($rsp_stat == "F")
    {
        //�쳣��Ϣ���÷����л�
        throw new Exception("[PPython Error] Receive Python exception: ".$rsp_msg, LAJP_EXCEPTION);
    }
    else
    {
        if ($rsp_msg != "N") //���ط�void
        {
            try {
            if (!unserialize($rsp_msg))
                if ($rsp_msg)
                    echo "receive ".$rsp_msg."<br/>\r\n";

            //�����л�
            return unserialize($rsp_msg);
            }
            catch (Exception $e){
                echo "receive ".$rsp_msg."<br/>\r\n";
            } 
        }
    }
}
?>
