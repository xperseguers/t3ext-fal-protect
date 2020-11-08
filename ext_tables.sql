#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE tx_falprotect_folder
(
    # management information
    storage int(11) DEFAULT '0' NOT NULL,

    identifier text,
    identifier_hash char(40) DEFAULT '' NOT NULL,

    # FE permissions
    fe_groups tinytext,

    KEY folder (storage,identifier_hash)
);

#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata
(
    starttime int(11) unsigned DEFAULT '0' NOT NULL,
    endtime   int(11) unsigned DEFAULT '0' NOT NULL
);
