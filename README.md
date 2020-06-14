# Ethereum Txpool Statistics

This is the code to create an Ethereum Txpool visualization. Link: [https://eth.zengo.link/queue](https://eth.zengo.link/queue)

![Txpool visualization](Demo.png?raw=true 'Txpool visualization')

The tool is a fork of [Dr. Jochen Hoenicke's](https://jochen-hoenicke.de) original [tool](https://github.com/jhoenicke/mempool) that perform similar functionality for Bitcoin and its forks.

### What do you see:

The tool displays three graphs:

#### Transaction count

This shows the number of transactions available in the txpool at a certain price level for unit of gas (gasPrice).
A steady count over time at a certain price level indicates that these transactions are not being mined, and the price is likely higher.

#### Pending fee

This shows the total amount of ETH currently available for miners to gain by mining the pending transactions.
This graph emphasizes high paying transactions. A very high paying transaction can increase the pools value dramatically without effecting the count.

#### TxPool Gas

Not all transactions are equal. Some are complicated smart contract instructions that demand a lot of gas.
This graph emphasizes "heavier" transactions.

### Interaction

- **Select** the timespan you wish to see from the selectors on top. `2h, 4h, 8h` etc.
  This depends of course on the amount of data gathered.

- **Zoom** on parts of data by dragging your cursor over a section of the graph

- **Filter** out low paying transaction by clicking on the price ranges in the legend.

## Installation instructions

### Installation: Part 1 - Installing Geth

You need to be running a bitcoin full [Geth](https://github.com/ethereum/go-ethereum) node.
Once you have it setup, run it with the following command to enable rpc and logging the txpool:

```
geth -rpc --rpcapi personal,eth,net,web3,miner,txpool --txpool.pricelimit 1000000000 --txpool.globalslots 1048576
```

By default, the txpool only stores 4096 transactions. Set the `txpool.globalslots` parameters based on the amount of transactions you would like to display.
Similarly, you can set the minimal price for transaction to filter out transactions with extremely low fees.

It is recommend to create a new unix user `mempool` and perform all actions by the dedicated user.
Clone the repository to the user's home:

```
sudo -H -u mempool bash
cd $HOME
https://github.com/KZen-networks/mempool.git
```

Edit `mempool.sh` script to adapt paths as necessary, especially the path to geth.
You can test your setup by running

```
geth attach --exec "txpool.status"
```

### Installation: Part 2 - setting up the database

Install `mysql` and create a database.
A database is required if you want to enable zooming to previous data events and auto-update.
If you don't want to use mysql, comment out the four lines starting with `open(SQL` at the end of
`mempool-sql.pl`.
In that case zooming and auto-update in the web interface won't work.

#### MySQL server installation instructions

##### Install the server and configure the root user

```
sudo apt install mysql-server
mysql_secure_installation
```

##### Setting up the users

Run `mysql` and create a new user:

`mysql> CREATE USER 'mempool'@'localhost' IDENTIFIED BY '<your-secret-password>';`

Create a file `.my.cnf` in the user home directory with the following content:

```
[client]
user=mempool
password=<your-secret-password>
```

Run `mysql` and create a new database:

```
mysql> create database eth_mempool;
```

Grant the `mempool` user and the web user access to the database:

```
mysql> rant all privileges on eth*mempool.* TO 'mempool'@'localhost' identified by '<your-secret-password>';
mysql> rant select on eth*mempool.* TO 'www'@'localhost' identified by '<your-secret-password>';
```

##### Creating the tables

Create the required tables in the database. Run the perl script in this directory.

```
perl mempool-create.pl | mysql eth_mempool
```

#### Testing the script

Finally, run the script:

```
./mempool.sh
```

You should see a new file created called `mempool.log` with update measurements.

You should be able to see a new entry in the SQL database:

```
mysql> select database eth_mempool;
mysql> select * from mempool;
```

There should be newly created files in `/dev/shm/mempool-eth` that contain the dynamic data the
webserver should serve.

#### Setting up the crontab entry

If everything looks fine add the following crontab entry (using `crontab -e`):

```
* * * * * /home/mempool/mempool/mempool.sh
```

This will run the script every minute, update the `mempool.log` file and the database.

### Installation: Part 3 - Web service

Install a web server of your choice (e.g. apache).
For refreshing/zooming you need php and php-mysql.
Link/copy the web subdirectory to the web root.
Finally link to the dynamic js files in `/dev/shm/mempool-eth`.

```
 cd $HOME/mempool/web/queue
 sudo ln -s $HOME/mempool/web/* /var/www/html
 ln -s /dev/shm/mempool-eth/*.js $HOME/mempool/web/queue/
```

#### Webserver configurations

Update the file `$HOME/mempool/web/queue/mempool.js` to point to your URL, the default is to locally server the JS files.

Update the file `db.php` with the correct password you used with user `www` when setting up the database.
This will allow the server to access the data and enable zooming functionality.

#### Optional: Apache installaion instructions for debian/ubuntu based systems

```
sudo apt install apache2
sudo apt install php7.2 libapache2-mod-php7.2 php-mysql
sudo apt install php-curl php-json php-cgi

sudo ufw allow "Apache"
sudo ufw allow 80
sudo service apache2 restart
```

Go to port 80 on your localhost and make sure you can see the landing screen
