#!/bin/bash
# -----------------------------------------------------------------------------
# NOTICE: Vagrant copies this file to /tmp/ on the guest machine prior
#         to running it! Do not rely on the script path!

echo "===================================================================="
echo "PREPARING ANSIBLE - installing ansible on guest machine"
echo "--------------------------------------------------------------------"

SCRIPT_PATH=/var/www/sites/project/build/ansible

ANSIBLE_CMD=`which ansible`

which ansible || (
	echo "Updating apt cache"
	apt-get update
	echo "Installing Ansible, sshpass"
	apt-get install -y ansible sshpass
)

cd $SCRIPT_PATH

echo "===================================================================="
echo "RUNNING ANSIBLE - configuring OS/software & executing 'phing setup'"
echo "please be patient! (this task will finish in a couple of minutes)"
echo "--------------------------------------------------------------------"
# show messages as they progress ...
export PYTHONUNBUFFERED=1
ansible-playbook site.yml --inventory-file=inventory-vagrant

