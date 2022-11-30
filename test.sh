PHP_VERSIOND="php:8.1"
echo "${PHP_VERSIOND}"
if [[ "${PHP_VERSIOND}" == *"7.3"* ]]
then
    echo "73"
fi

if [[ "${PHP_VERSIOND}" == *"7.4"* ]]
then
    echo "74"
fi

if [[ "${PHP_VERSIOND}" == *"8.1"* ]]
then
    echo "81"
fi