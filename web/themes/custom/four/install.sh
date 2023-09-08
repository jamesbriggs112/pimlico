echo Welcome to the Four Base Theme installer
read -p 'Enter the name of your theme: ' theme_name
read -p 'Enter a description for your theme: ' theme_desc

echo Creating info file
sed "s/FOUR_BASE_THEME/$theme_name/g; s/MARMELO_BASE_THEME_NAME/$theme_name/g; s/MARMELO_BASE_THEME_DESCRIPTION/$theme_desc/g" ./FOUR_BASE_THEME.info.yml > $theme_name.info.yml

echo Creating theme file
sed "s/FOUR_BASE_THEME/$theme_name/g" ./FOUR_BASE_THEME.theme > $theme_name.theme

echo Creating libraries file
cp FOUR_BASE_THEME.libraries.yml ./$theme_name.libraries.yml

rm -Rf FOUR_BASE_THEME.*

yarn install
