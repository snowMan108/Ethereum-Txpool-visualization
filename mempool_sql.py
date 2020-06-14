#!/usr/bin/env python3
import json
import sys
import time
import yaml
from subprocess import PIPE, Popen

MYSQL = "/usr/bin/mysql"
MEMPOOLLOG = "mempool.log"
MYSQLMEMPOOLDB = "eth_mempool"

FEELIMIT = [0.0001, 1, 2, 3, 4, 5, 6, 7, 8, 10,
            12, 14, 17, 20, 25, 30, 40, 50, 60, 70, 80, 100,
            120, 140, 170, 200, 250, 300, 400, 500, 600, 700, 800, 1000,
            1200, 1400, 1700, 2000, 2500, 3000, 4000, 5000, 6000, 7000, 8000, 10000]
sizes = [0] * len(FEELIMIT)
count = [0] * len(FEELIMIT)
fees = [0] * len(FEELIMIT)
found = False

def parse_txdata(obj):
    global sizes, count, fees, found
    # print(obj)
    try:
        if "gasPrice" in obj:
            gprice =  int(obj["gasPrice"], 0)
            gas = int(obj["gas"], 0)
            size = gas
            gprice = gprice / 1000000000

            found = True
            for i, limit in enumerate(FEELIMIT):
                if (gprice >= limit and
                        (i == len(FEELIMIT) - 1 or gprice < FEELIMIT[i+1])):
                    sizes[i] += size
                    count[i] += 1
                    # Fees in ETH
                    fees[i] += round(gprice * gas)
                    break
            return None
        return obj
    except:
        return None

def dump_data(timestamp, sizes, count, fees):
    sizesstr = ",".join(str(x) for x in sizes)
    countstr = ",".join(str(x) for x in count)
    feesstr = ",".join(str(x) for x in fees)
    # print("[{:d},[{}],[{}],[{}]],\n"
    #                   .format(timestamp, countstr, sizesstr, feesstr))
    with open(MEMPOOLLOG, "a") as logfile:
        logfile.write("[{:d},[{}],[{}],[{}]],\n"
                      .format(timestamp, countstr, sizesstr, feesstr))
    proc = Popen([MYSQL, MYSQLMEMPOOLDB], stdin=PIPE, stdout=PIPE)
    proc.communicate("INSERT INTO mempool VALUES({:d},{},{},{});\n"
                     .format(timestamp, countstr, sizesstr, feesstr)
                     .encode("ascii"))

def main():
    global sizes, count, fees, found
    timestamp = int(time.time())
    try:
        json.load(sys.stdin, object_hook=parse_txdata)
    except:
        pass
    if found:
        dump_data(timestamp, sizes, count, fees)

main()
