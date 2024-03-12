#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE tx_falprotect_folder
(
    # required in TYPO3 v8
    uid int(10) unsigned NOT NULL auto_increment,
    pid int(10) unsigned NOT NULL default '0',
    tstamp int(10) unsigned NOT NULL default '0',
    crdate int(10) unsigned NOT NULL default '0',

    # management information
    storage int(11) DEFAULT '0' NOT NULL,

    identifier text,
    identifier_hash char(40) DEFAULT '' NOT NULL,

    # FE permissions
    fe_groups tinytext,

    PRIMARY KEY (uid),
    KEY folder (storage,identifier_hash),
    KEY parent (pid)
);

#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata
(
    visible int(11) unsigned DEFAULT '1',
    starttime int(11) unsigned DEFAULT '0' NOT NULL,
    endtime   int(11) unsigned DEFAULT '0' NOT NULL,

    # FE permissions
    fe_groups tinytext
);
