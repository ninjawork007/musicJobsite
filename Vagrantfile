# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure("2") do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://atlas.hashicorp.com/search.
  config.vm.box = "ubuntu/trusty64"

  # Disable automatic box update checking. If you disable this, then
  # boxes will only be checked for updates when the user runs
  # `vagrant box outdated`. This is not recommended.
  # config.vm.box_check_update = false

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:8080" will access port 80 on the guest machine.
  # config.vm.network "forwarded_port", guest: 80, host: 8080

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
  config.vm.network "private_network", ip: "192.168.33.10"

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network "public_network"

  # Share an additional folder to the guest VM. The first argument is
  # the path on the host to the actual folder. The second argument is
  # the path on the guest to mount the folder. And the optional third
  # argument is a set of non-required options.
  config.vm.synced_folder ".", "/data", type: "nfs", mount_options: ['rw', 'vers=3', 'tcp'], linux__nfs_options: ['rw','no_subtree_check','all_squash','async']

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  # Example for VirtualBox:
  #
  config.vm.provider "virtualbox" do |vb|
  #   # Display the VirtualBox GUI when booting the machine
  #   vb.gui = true
  #
  #   # Customize the amount of memory on the VM:
    vb.memory = "3033"
  end
  #
  # View the documentation for the provider you are using for more
  # information on available options.

  # Define a Vagrant Push strategy for pushing to Atlas. Other push strategies
  # such as FTP and Heroku are also available. See the documentation at
  # https://docs.vagrantup.com/v2/push/atlas.html for more information.
  # config.push.define "atlas" do |push|
  #   push.app = "YOUR_ATLAS_USERNAME/YOUR_APPLICATION_NAME"
  # end

  # Enable provisioning with a shell script. Additional provisioners such as
  # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
  # documentation for more information about their specific syntax and use.
  # config.vm.provision "shell", inline: <<-SHELL
  #   apt-get update
  #   apt-get install -y apache2
  # SHELL
  
  config.vm.provision "shell", inline: <<-SHELL
  
    # Update and install packages
    apt-get update    
    DEBIAN_FRONTEND=noninteractive apt-get install -y --force-yes accountsservice acl acpid adduser apache2 apache2-bin apache2-data apache2-mpm-prefork apache2-utils apache2.2-bin apparmor apport apport-symptoms apt apt-transport-https apt-utils apt-xapian-index aptitude aptitude-common at autoconf automake autotools-dev base-files base-passwd bash bash-completion bc bind9-host binutils bsdmainutils bsdutils build-essential busybox-initramfs busybox-static byobu bzip2 ca-certificates cloud-guest-utils cloud-image-utils cloud-init cloud-initramfs-growroot cloud-initramfs-rescuevol cloud-utils comerr-dev command-not-found command-not-found-data console-setup coreutils cpio cpp cpp-4.6 cpp-4.8 crda cron curl dash dbconfig-common dbus debconf debconf-i18n debhelper debianutils dh-apparmor dh-python diffutils distro-info distro-info-data dmidecode dmsetup dnsutils dosfstools dpkg dpkg-dev e2fslibs e2fsprogs eatmydata ed eject euca2ools fakeroot file findutils fontconfig-config fonts-dejavu-core fonts-ubuntu-font-family-console friendly-recovery ftp fuse g++ g++-4.8 gawk gcc gcc-4.6 gcc-4.6-base gcc-4.8 gcc-4.8-base gcc-4.9-base gdisk genisoimage geoip-database gettext gettext-base gir1.2-glib-2.0 gir1.2-gudev-1.0 gnupg gpgv grep groff-base grub-common grub-gfxpayload-lists grub-legacy-ec2 grub-pc grub-pc-bin grub2-common gzip hdparm hostname ifupdown info init-system-helpers initramfs-tools initramfs-tools-bin initscripts insserv install-info intltool-debian iproute iproute2 iptables iputils-ping iputils-tracepath irqbalance isc-dhcp-client isc-dhcp-common iso-codes iw javascript-common kbd keyboard-configuration klibc-utils kmod krb5-locales krb5-multidev lame landscape-client landscape-common language-selector-common laptop-detect less libaccountsservice0 libacl1 libaio1 libalgorithm-diff-perl libalgorithm-diff-xs-perl libalgorithm-merge-perl libapache2-mod-php5 libapparmor-perl libapparmor1 libapr1 libaprutil1 libaprutil1-dbd-sqlite3 libaprutil1-ldap libapt-inst1.5 libapt-pkg4.12 libarchive-extract-perl libasan0 libasn1-8-heimdal libasound2 libasound2-data libasprintf-dev libasprintf0c2 libatm1 libatomic1 libattr1 libaudit-common libaudit1 libbind9-90 libblkid1 libboost-iostreams1.54.0 libboost-system1.54.0 libboost-thread1.54.0 libbsd0 libbz2-1.0 libc-bin libc-dev-bin libc6 libc6-dev libcap-ng0 libcap2 libcap2-bin libcgmanager0 libck-connector0 libclass-accessor-perl libclass-isa-perl libcloog-isl4 libcomerr2 libcroco3 libcurl3 libcurl3-gnutls libcurl4-openssl-dev libcwidget3 libdb5.1 libdb5.3 libdbd-mysql-perl libdbi-perl libdbus-1-3 libdbus-glib-1-2 libdebconfclient0 libdevmapper-event1.02.1 libdevmapper1.02.1 libdns100 libdpkg-perl libdrm-intel1 libdrm-radeon1 libdrm2 libedit2 libelf1 libept1.4.12 libestr0 libevent-2.0-5 libexpat1 libfakeroot libffi6 libfile-fcntllock-perl libflac8 libfontconfig1 libfreetype6 libfribidi0 libfuse2 libgc1c2 libgcc-4.8-dev libgcc1 libgck-1-0 libgcr-3-common libgcr-base-3-1 libgcrypt11 libgcrypt11-dev libgd3 libgdbm3 libgeoip1 libgettextpo-dev libgettextpo0 libgirepository-1.0-1 libglib2.0-0 libgmp10 libgnutls-dev libgnutls-openssl27 libgnutls26 libgnutlsxx27 libgomp1 libgpg-error-dev libgpg-error0 libgpm2 libgsm1 libgssapi-krb5-2 libgssapi3-heimdal libgssrpc4 libgudev-1.0-0 libhcrypto4-heimdal libheimbase1-heimdal libheimntlm0-heimdal libhtml-template-perl libhx509-5-heimdal libicu52 libidn11 libidn11-dev libio-string-perl libisc95 libisccc90 libisccfg90 libisl10 libitm1 libiw30 libjbig0 libjpeg-turbo8 libjpeg8 libjs-codemirror libjs-jquery libjs-jquery-cookie libjs-jquery-event-drag libjs-jquery-metadata libjs-jquery-mousewheel libjs-jquery-tablesorter libjs-jquery-ui libjs-underscore libjson-c2 libjson0 libk5crypto3 libkadm5clnt-mit9 libkadm5srv-mit8 libkadm5srv-mit9 libkdb5-7 libkeyutils1 libklibc libkmod2 libkrb5-26-heimdal libkrb5-3 libkrb5-dev libkrb5support0 libldap-2.4-2 libldap2-dev liblocale-gettext-perl liblockfile-bin liblockfile1 liblog-message-simple-perl libltdl-dev libltdl7 liblwres90 liblzma5 libmagic1 libmail-sendmail-perl libmcrypt4 libmemcached10 libmodule-pluggable-perl libmount1 libmp3lame0 libmpc3 libmpdec2 libmpfr4 libmysqlclient18 libncurses5 libncursesw5 libnet-daemon-perl libnewt0.52 libnfnetlink0 libnih-dbus1 libnih1 libnl-3-200 libnl-genl-3-200 libnspr4 libnss3 libnss3-nssdb libnuma1 libogg0 libopencore-amrnb0 libopencore-amrwb0 libp11-kit-dev libp11-kit0 libpam-cap libpam-modules libpam-modules-bin libpam-runtime libpam-systemd libpam0g libparse-debianchangelog-perl libparted0debian1 libpcap0.8 libpci3 libpciaccess0 libpcre3 libpcre3-dev libpcrecpp0 libpcsclite1 libpipeline1 libplrpc-perl libplymouth2 libpng12-0 libpod-latex-perl libpolkit-agent-1-0 libpolkit-backend-1-0 libpolkit-gobject-1-0 libpopt0 libprocps3 libpython-stdlib libpython2.7 libpython2.7-minimal libpython2.7-stdlib libpython3-stdlib libpython3.4-minimal libpython3.4-stdlib libquadmath0 librados2 librbd1 libreadline5 libreadline6 libroken18-heimdal librtmp-dev librtmp0 libsasl2-2 libsasl2-modules libsasl2-modules-db libselinux1 libsemanage-common libsemanage1 libsepol1 libserf-1-1 libsigc++-2.0-0c2a libsigsegv2 libslang2 libsndfile1 libsox-fmt-alsa libsox-fmt-base libsox2 libsqlite3-0 libss2 libssl-dev libssl-doc libssl1.0.0 libstdc++-4.8-dev libstdc++6 libsub-name-perl libsvn1 libswitch-perl libsys-hostname-long-perl libsystemd-daemon0 libsystemd-login0 libtasn1-3-dev libtasn1-6 libtasn1-6-dev libterm-readkey-perl libterm-ui-perl libtext-charwidth-perl libtext-iconv-perl libtext-soundex-perl libtext-wrapi18n-perl libtiff5 libtimedate-perl libtinfo5 libtool libtsan0 libudev1 libunistring0 libusb-0.1-4 libusb-1.0-0 libustr-1.0-1 libuuid1 libvorbis0a libvorbisenc2 libvorbisfile3 libvpx1 libwavpack1 libwhoopsie0 libwind0-heimdal libwrap0 libx11-6 libx11-data libxapian22 libxau6 libxcb1 libxdmcp6 libxext6 libxml2 libxmuu1 libxpm4 libxslt1.1 libxtables10 libyaml-0-2 linux-firmware linux-headers-3.13.0-55 linux-headers-3.13.0-55-generic linux-headers-3.13.0-57 linux-headers-3.13.0-57-generic linux-headers-3.13.0-93 linux-headers-3.13.0-93-generic linux-headers-3.13.0-95 linux-headers-3.13.0-95-generic linux-headers-3.13.0-96 linux-headers-3.13.0-96-generic linux-headers-generic linux-libc-dev locales lockfile-progs login logrotate lsb-base lsb-release lshw lsof ltrace lvm2 m4 make makedev man-db manpages manpages-dev mawk memcached memtest86+ mime-support mlocate module-init-tools mount mountall mtr-tiny multiarch-support mysql-client-5.5 mysql-client-core-5.5 mysql-common mysql-server mysql-server-5.5 mysql-server-core-5.5 nano ncurses-base ncurses-bin ncurses-term net-tools netbase netcat-openbsd ntfs-3g ntpdate openssh-client openssh-server openssh-sftp-server openssl os-prober parted passwd patch pciutils perl perl-base perl-modules php-apc php-gettext php-pear php5-apcu php5-cli php5-common php5-curl php5-dev php5-gd php5-intl php5-json php5-mcrypt php5-memcached php5-mysql php5-readline phpmyadmin pkg-config pkg-php-tools plymouth plymouth-theme-ubuntu-text po-debconf policykit-1 popularity-contest powermgmt-base ppp pppconfig pppoeconf procps psmisc python python-apport python-apt python-apt-common python-boto python-chardet python-cheetah python-configobj python-crypto python-dbus python-dbus-dev python-debian python-distro-info python-gdbm python-gi python-gnupginterface python-httplib2 python-json-pointer python-jsonpatch python-keyring python-launchpadlib python-lazr.restfulclient python-lazr.uri python-lxml python-m2crypto python-minimal python-newt python-oauth python-openssl python-pam python-paramiko python-pkg-resources python-prettytable python-problem-report python-pycurl python-requestbuilder python-requests python-secretstorage python-serial python-setuptools python-simplejson python-six python-software-properties python-twisted-bin python-twisted-core python-twisted-names python-twisted-web python-urllib3 python-wadllib python-xapian python-yaml python-zope.interface python2.7 python2.7-minimal python3 python3-apport python3-apt python3-commandnotfound python3-dbus python3-distupgrade python3-gdbm python3-gi python3-minimal python3-problem-report python3-pycurl python3-software-properties python3-update-manager python3.4 python3.4-minimal qemu-utils readline-common resolvconf rsync rsyslog run-one screen sed sensible-utils sgml-base sharutils shtool software-properties-common sox ssh-import-id ssl-cert strace subversion sudo systemd-services systemd-shim sysv-rc sysvinit-utils tar tasksel tasksel-data tcpd tcpdump telnet time tmux ttf-dejavu-core tzdata ubuntu-keyring ubuntu-minimal ubuntu-release-upgrader-core ubuntu-standard ucf udev ufw unattended-upgrades unzip update-manager-core update-notifier-common upstart ureadahead usbutils util-linux uuid-runtime vim vim-common vim-runtime vim-tiny w3m watershed wget whiptail whoopsie wireless-regdb wireless-tools wpasupplicant xauth xkb-data xml-core xz-utils zip zlib1g zlib1g-dev linux-image-3.13.0-55-generic linux-image-3.13.0-57-generic linux-image-3.13.0-93-generic linux-image-3.13.0-95-generic linux-image-3.13.0-96-generic linux-headers-virtual linux-image-virtual linux-virtual php5-mongo mongodb mongodb-dev mongodb-server mongodb-clients libboost-filesystem1.54.0 libboost-dev libboost1.54-dev libboost-program-options1.54.0 libgoogle-perftools4 libsnappy1 libv8-3.14.5 libtcmalloc-minimal4 libunwind8

    # Create DB if needed
    if ! mysql -u root -e 'use vocalizr;'; then
    	echo "Creating vocalizr database..."
    	mysqladmin -u root create vocalizr 
    fi
    # Create MongoDB user
    mongo vocalizr --eval "db.addUser('vocalizr_user', 'letmein')"
    
    # Enable apache2 modules
    sudo a2enmod headers
    sudo a2enmod rewrite
    
    # Change apache config
    sudo cat <<APACHE_CONFIG >/etc/apache2/sites-enabled/000-default.conf
<VirtualHost *:80>	
	ServerName local.vocalizr.com
        ServerAlias dev.vocalizr.com
        ServerAdmin robert@vocalizr.com
	DocumentRoot /data/web
	<Directory /data/web/>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride None
		Require all granted
		Order allow,deny
		allow from all
		<IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ /app.php [QSA,L]
        </IfModule>
	</Directory>
	ErrorLog /var/log/apache2/vocalizr.dev-error.log
	LogLevel debug
	CustomLog /var/log/apache2/vocalizr.dev-access.log combined
</VirtualHost>
APACHE_CONFIG

    # Restarting apache to activate changes 
    sudo service apache2 restart

  SHELL
  
end
