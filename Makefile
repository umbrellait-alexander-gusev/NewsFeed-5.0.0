include ./docker/.env
export $(shell sed 's/=.*//' ./docker/.env)

.DEFAULT_GOAL := status

MAKEFLAGS = --no-print-directory

DC_FILE = ./docker/docker-compose.yml

RUN = docker-compose -f $(DC_FILE) run  --rm --no-deps
BUILD = docker-compose -f $(DC_FILE) build
UP = docker-compose -f $(DC_FILE) up
DOWN = docker-compose -f $(DC_FILE) down
RM = docker-compose -f $(DC_FILE) rm
STOP = docker-compose -f $(DC_FILE) stop
PS = docker-compose -f $(DC_FILE) ps
LOGS = docker-compose -f $(DC_FILE) logs

# We use docker-compose exec -T in order to add compatibility with Jenkins
# https://github.com/docker/compose/issues/3352#issuecomment-221526576
EXEC_DRY = docker-compose -f $(DC_FILE) exec -T
EXEC = docker-compose -f $(DC_FILE) exec

# Get the user/group ID of the current user, and use them to override users/groups in containers wchich compile files
CURRENT_UID = $(shell id -u || 1000)
CURRENT_GID = $(shell id -g || 50)

# These commands create an environment
docker-env: stop \
			etc-files
	$(BUILD)
	$(MAKE) start

# Delete CONTAINERS ONLY of CURRENT docker-compose.yaml of CURRENT 'COMPOSE_PROJECT_NAME' from .env
# ALL CONTAINERS OF OTHER 'COMPOSE_PROJECT_NAME' from .env WON'T BE DELETED
# -f - force, without confirmation; -s - stop containers if need
clean-containers:
	-$(RM) -fs

down:
	$(DOWN) -v --remove-orphans
	$(MAKE) status

up:
	$(UP) -d
	$(MAKE) status

up-node:
up-%:
	$(UP) -d $*
	$(MAKE) status

restart:
	$(MAKE) stop
	$(MAKE) start

restart-node:
restart-%:
	$(MAKE) stop-$*
	$(MAKE) start-$*

stop:
	-$(STOP)
	$(MAKE) status

stop-node:
stop-%:
	$(STOP) $*
	$(MAKE) status

status:
	$(PS)

# Consoles section
# Compatibility with CLI autocomplete
c-node:
c-%:
	$(EXEC) -u root $* sh

# Use SHIFT + G, G, CMD + SHIFT + G, CMD + G to jump to start/end of logs
# Compatibility with CLI autocomplete
l-node:
l-%:
	$(LOGS) $* | less -R

# Compatibility with CLI autocomplete
ll-node:
ll-%:
	$(LOGS) -f $*

# Create files for /etc/passwd and /etc/group, so that compiled/created files match current system user
# `nobody` user for `npm`
etc-files:
	mkdir -p assets
	touch assets/passwd
	echo "root:x:0:0:root:/root:/bin/bash\nnobody:x:1:1:nobody:/:/bin/bash\nhost:x:$(CURRENT_UID):$(CURRENT_GID):host:/home/host:/bin/bash" > assets/passwd
	touch assets/group
	echo "root:x:0:\nnobody:x:1:\nhost:x:$(CURRENT_GID):" > assets/group