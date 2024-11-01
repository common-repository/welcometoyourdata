
rm -rf wp-wtyd
rm wp-wtyd.zip

mkdir wp-wtyd
cp *.php wp-wtyd/
cp *.png wp-wtyd/
cp *.html wp-wtyd/
cp -r lib wp-wtyd/
cp -r js wp-wtyd/
cp -r images wp-wtyd/


zip wp-wtyd wp-wtyd/*

