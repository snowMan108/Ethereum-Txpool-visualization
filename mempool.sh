#!/bin/bash

DESTDIR=/dev/shm/mempool-eth
# Change this path to your geth executable
GETH=/home/mempool/go/bin/geth
MEMPOOLHOME=/home/ubuntu/mempool
TMPFILE=$DESTDIR/rawdump.txt
export DESTDIR MEMPOOLHOME

cd $MEMPOOLHOME

# create ram-disk directory if it does not exists
if [ ! -e $DESTDIR ]; then
    mkdir -p $DESTDIR/LOCK
    # read mempool.log once sequentially to quickly load it in buffers
    cat mempool.log > /dev/null
    ./mkdata.sh
    rmdir $DESTDIR/LOCK
fi

# create mempool statistics, protected by LOCK
if ! mkdir $DESTDIR/LOCK 2>/dev/null; then
    exit
fi
GETH attach --exec "console.log(JSON.stringify(txpool.content.pending, null ,2))" > $TMPFILE
python3 mempool_sql.py < $TMPFILE
rmdir $DESTDIR/LOCK

# update ram-disk directory, protected by DATALOCK
if ! mkdir $DESTDIR/DATALOCK 2>/dev/null; then
    exit
fi
./updatedata.sh
rmdir $DESTDIR/DATALOCK
