# Instructions:
1. Make redis dump, get directory of dump and download it locally
```bash
redis-cli -p 6370 SAVE


redis-cli -p 6370 CONFIG GET DIR


scp ssh_server:/path/to/dump.rdb ./
```
2. Install rdb: https://github.com/sripathikrishnan/redis-rdb-tools 
3. Convert rdb file to csv: 
```bash
rdb -c memory dump.rdb -f memory.csv
```
4. copy redis_memory_analyzer.php to dir with dump and edit path to csv file:
```php 
$csvFilePath = __DIR__ .'/memory.csv'; 
```

5. run php redis_memory_analyzer.php.
6. Check output or open stats.html file in browser.


# Stats.html file
Columns are self-described. To sort by column - press on it once or twice (once - ASC, twice - DESC)