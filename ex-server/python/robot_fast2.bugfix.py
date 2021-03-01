#!/usr/bin/python
#coding=UTF-8
import sys
import urllib2
import urllib
import random
import math
import time
import os
import requests
import ConfigParser
import subprocess
import MySQLdb
import json
import thread
#reload(sys)
#sys.setdefaultencoding('utf-8')

def get_conf(name):
    cp = ConfigParser.SafeConfigParser()
    the_dir = sys.path[0]
    #print the_dir
    cp.read(the_dir+'/db.conf')
    return cp.get('db',name)

def Conn():
    cp = ConfigParser.SafeConfigParser()
    the_dir = sys.path[0]
    print the_dir
    cp.read(the_dir+'/db.conf')
    return MySQLdb.connect(host=cp.get('db', 'host'),user=cp.get('db', 'user'),passwd=cp.get('db', 'pass'),db=cp.get('db', 'name'),port=int(cp.get('db', 'port')),charset=cp.get('db', 'charset'))
    #return MySQLdb.connect(host='127.0.0.1',user='root',passwd='root',db='kkwallet',port=3306, charset="utf8")

def rpc_query(params):
    host = 'http://' + get_conf('corehost') + ':8080'
    headers = {'Content-Type': 'application/json', 'Connection':'close'}
    post_data = {"id":22,"method":"order.depth","params":params}
    encode_json = json.dumps(post_data)
    #print encode_json
    try:
        P_post=requests.post(host,headers=headers,data=encode_json,timeout=5)
        if P_post.status_code == 200:
            rst = json.loads(P_post.text)
            #print rst
            if rst.has_key('result'):
                return rst['result']
            else:
                return None
        else:
            return None
    except requests.RequestException as e:
        print e
        return None   


    
def get_num(price,asks,bids,type):
 
    data_ask = asks
    data_bid = bids             
    #print data_ask
    #print data_bid
    price = float(price)
    total_num = 0
    print "=====type===="
    print type
    if(type==2):
        for item in data_ask:
            p = float(item[0])
            num =  float(item[1])
            #print p
            #print price
            if(p <= price):
                total_num = total_num + num
                #print '###'+  str(num)
    else:
         for item in data_bid:
            p = float(item[0])
            num =  float(item[1])
            if(p>=price):
                total_num = total_num + num    
                print '###'+  str(num)
    #print total_num
    return float(total_num)

def get_depth(market,decimals):
    prec_num = 1.0;
    while decimals>0:
        prec_num =  prec_num/10
        decimals = decimals -1
    param =  [market,50,str(prec_num)]
    data =  rpc_query(param)

    if data is None:
        return 0,0
    data_ask = data['asks']
    data_bid = data['bids']
    print "=====ask======"
    print data_ask
    print "=====bid======"
    print data_bid
    return len(data_ask),len(data_bid),data_ask,data_bid

def execOrder(info,marketinfo,decimalsinfo):
    print '-------------' 
    market_name = marketinfo[int(info[3])]
    decimals = int(decimalsinfo[int(info[3])] / 1)
    
    #if market_name != 'XRPUSDT':
        #return
    print 'market_name:' + str(market_name)
    depth_count = get_depth(market_name,decimals)

    ask_count = depth_count[0]
    print 'ask count:' + str(ask_count)
    bid_count = depth_count[1]
    print 'bid count:' + str(bid_count)
    order_count = 1
    
    paytype = random.randint(1, 2) #2买入 1卖出
    fill = 0
    if(bid_count < 30 and bid_count < ask_count):
        paytype = 2
        order_count = 30 - bid_count
        fill = 1
    if(ask_count < 30 and bid_count >= ask_count):
        paytype = 1
        order_count = 30 - ask_count
        fill = 1
    
    print 'order type:' + str(paytype)
    print 'order count:' + str(order_count)

    while order_count>0:
        order_count = order_count -1
        price = random.uniform(float(info[4]), float(info[5]))
        depth_count = get_depth(market_name,decimals)
        ask_count = depth_count[0]
        print 'ask count:' + str(ask_count)
        bid_count = depth_count[1]
        print 'bid count:' + str(bid_count)
        if(fill == 1):
            print "=====bid_ask_count======"
            print bid_count
            print ask_count
            if(paytype == 1):
                if(bid_count>0):
                    min_price = float(depth_count[3][0][0])
                    if(min_price >= float(info[4]) and min_price <= float(info[5])):
                        price = random.uniform(min_price, float(info[5]))
                        print "=====sell_price======"
                        print price
                    else:
                        print "=====sell_price_error======"
                        print price
                        print depth_count[2]
                        print depth_count[3]
                        print min_price
                        print float(info[4])
                        print float(info[5])
                        if(min_price > float(info[5])):
                            order_count = 0
            if(paytype == 2):
                if(ask_count > 0):
                    max_price = float(depth_count[2][0][0])
                    if(max_price >= float(info[4]) and max_price <= float(info[5])):
                        price = random.uniform(float(info[4]), max_price)
                        print "=====buy_price======"
                        print price
                    else:
                        print "=====buy_price_error======"
                        print price
                        print depth_count[2]
                        print depth_count[3]
                        print max_price
                        print float(info[4])
                        print float(info[5])
                        if (max_price < float(info[4])):
                            order_count = 0
        total_num = get_num(price,depth_count[2],depth_count[3], paytype)
        print "=====total_num======"
        print str(total_num)
        if(total_num == 0):
            num = random.uniform(float(info[6]), float(info[7]))
        else:
            num = total_num
    
        num = round(num, 8) #取8位小数
        if market_name.endswith("USD"):
            num = int(num)
		
        print 'num:'+ str(num)

        price = round(price, 8)
        print 'price:'+ str(price)

        textmod={"access_token":str(info[0]),"market":marketinfo[int(info[3])],"side":paytype,"amount":str(num),"pride":str(price)}
            
        #print textmod
        textmod = urllib.urlencode(textmod)
        url = 'http://' + get_conf('domain') + '/api/exchange/order-limit'
        req = urllib2.Request(url=url,data=textmod)
        res = urllib2.urlopen(req)
        res = res.read()
        print(res)
    
def lastUpdateTime(cur):
    assets_count = cur.execute("select symbol from jl_coins where enable = 1 order by listorder desc")
    #print 'db have %d coins' % assets_count
    assets = [k[0] for k in cur.fetchall()]
    #print assets

    assetss = (','.join("'%s'" % k for k in assets))

    markets_count = cur.execute("select stock,money,id,decimals from jl_exchange_coins where enable = 1 and stock in (%s) and money in (%s) order by listorder desc" % (assetss,assetss))
    #print 'db have %d market' % markets_count
    old_markets = cur.fetchall()
    marketinfo = {k[2]:k[0]+k[1] for k in old_markets}
    decimalsinfo = {k[2]:k[3] for k in old_markets}
    
    #print marketinfo

    marketids = (','.join('%d' % k for k in [j[2] for j in old_markets]))
    #print marketids
    
    cur.execute('SELECT b.access_token,a.* FROM `jl_robot` a INNER JOIN `jl_api_access_token` b ON a.uid = b.`user_id` where a.market_id in (%s) and a.status = 1 LIMIT 60' % (marketids))
    data = cur.fetchall()
    #print data
    if data:
        for info in data:
            thread.start_new_thread(execOrder,(info,marketinfo,decimalsinfo))
            #time.sleep(0.1)

    else:
        print u'may be error ...'

if __name__ == '__main__':
    while True:
        try:
	    conn= Conn()
	    cur=conn.cursor()
            lastUpdateTime(cur)
            cur.close()
            conn.close()
	    print "=====main thread over======"
            time.sleep(16)
        except Exception,ex:
            print Exception,":",ex
            time.sleep(5)
