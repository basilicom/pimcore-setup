#
# Simple helper to shell into ypur vagrant bon on a windows machine
#

if test -z "$*"
then
    # no extra parameters, start interactive shell:
    ssh -o StrictHostKeyChecking=no -i ~/.vagrant.d/insecure_private_key -p2222 vagrant@127.0.0.1
else
    ssh -o StrictHostKeyChecking=no -i ~/.vagrant.d/insecure_private_key -p2222 vagrant@127.0.0.1 "cd /var/www/sites/project/build/ ; $*"
fi
