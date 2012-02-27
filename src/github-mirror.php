#! /usr/bin/env php

<?php

$help = <<<EOL
Usage: github-mirror.php USERNAME PASSWORD BACKUP_PATH
EOL;

if (count($argv) != 4) {
  file_put_contents('php://stderr', "Incorrect number of arguments\n");
  echo $help;
  exit(1);
}
$username = $argv[1];
$password = $argv[2];
$backup_path = $argv[3];

if (!file_exists($backup_path)) {
  if (!mkdir($backup_path, 0777, true)) {
    throw new Exception("unable to create $backup_path");
  }
} else if(!is_dir($backup_path)) {
  throw new Exception("backup_path $backup_path is not a directory");
}
chdir($backup_path);

$ch = curl_init('https://api.github.com/user/repos');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

curl_setopt($ch, CURLOPT_SSH_AUTH_TYPES, CURLSSH_AUTH_PUBLICKEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$repos = json_decode(curl_exec($ch));
curl_close($ch);

foreach($repos as $repo) {
  if (!file_exists("$backup_path/$repo->name")) {
    shell_exec("git clone $repo->ssh_url $repo->name");
  } else {
    chdir($repo->name);
    shell_exec('git pull --rebase origin master');
    chdir($backup_path);
  }
}

?>

