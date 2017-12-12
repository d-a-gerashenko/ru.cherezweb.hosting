_current_dir=`dirname $0`
cd $_current_dir
cat ./pid.txt | awk -F '@' '{print $1}' | xargs kill