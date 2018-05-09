<html>
    <head>

    </head>
    <body style='margin: 0;padding: 0;'>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td>
                <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse;">
                    <tr>
                        <td bgcolor="#2A2A36" style="padding-bottom: 18px;border-top-left-radius: 6px;border-top-right-radius: 6px;">
                           <img src="http://testapi.novaby.com/images/logo.fa32f48.png" alt="Creating Email Magic"  style="display: block;margin-top: 18px;margin-left: 36px;"/>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#3E3E4D" style="padding-left: 36px;padding-right: 36px;">
                            <table cellpadding="0" cellspacing="0" width="100%" style="color:#FFFFFF;line-height: 26px;font-size: 24px;">
                                <tr>
                                    <td style="padding-top: 40px;">
                                        <span style="font-size: 24px;">Hello,{{$user or 'Nanwu'}}</span>
                                    </td>
                                </tr>
                                    <tr>
                                        <td style="padding-top: 19px;font-size: 17px;">
                                            @yield('content')
                                        </td>
                                    </tr>
                                @if(isset($url))
                                    <tr>
                                        <td style="text-align: center;padding-top: 40px;">
                                            <a href="{{$url}}" style="color:#FFFFFF;text-align: center;text-decoration: none;border-radius: 100px;background-color: #EA6264;padding: 10px 15px 10px 15px;font-size: 24px;">Click to view</a>
                                        </td>
                                    </tr>
                                @endif
                                    <tr>
                                        <td style="padding-top: 40px;font-size: 17px;">
                                            As always, if you have any questions or issues, please contact us here: info@novaby.com or on our forum. We're here to help you get the most out of your Novaby exper
                                        </td>
                                    </tr>
                                <tr>
                                    <td style="padding-top: 40px;padding-bottom: 40px;font-size: 17px;">
                                        The Novaby team
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#2A2A36" style="padding-bottom: 20px;padding-top: 20px;border-bottom-left-radius: 6px;border-bottom-right-radius: 6px;color: #FFFFFF;font-size: 14px;">
                            <span style="float: left;padding-left: 36px;line-height: 37px;font-size: 17px;">Follow us</span>
                            <a href="https://www.facebook.com/NovabyCompany/" style="font-size: 0;"><img src="http://testapi.novaby.com/images/icon_facebook.png" style="padding-left: 16px;"/></a>
                            <a href="https://twitter.com/novabycompany/" style="font-size: 0;"><img src="http://testapi.novaby.com/images/icon_twitter.png" style="padding-left: 12px;"/></a>
                            <a href="http://www.linkedin.com/company/novaby/" style="font-size: 0;"><img src="http://testapi.novaby.com/images/icon_Linkedin.png" style="padding-left: 12px;"/></a>
                            <span style="float: right;padding-right: 36px;font-size: 14px;color:#8D8D8D;line-height: 37px;">© Novaby 2017</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </body>
</html>