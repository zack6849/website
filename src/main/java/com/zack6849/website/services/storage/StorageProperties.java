package com.zack6849.website.services.storage;

import org.springframework.boot.context.properties.ConfigurationProperties;

import java.util.Arrays;
import java.util.List;

@ConfigurationProperties(prefix = "storage")
public class StorageProperties {
    private String blacklist = "default";
    private String mediapath = "media";
    private String uploadDestination;
    private String wallpaperDestination;

    public List<String> getExtensionBlacklist() {
        return Arrays.asList(getBlacklist().split(" "));
    }

    public String getBlacklist(){
        return this.blacklist;
    }

    public void setBlacklist(String blacklist){
        this.blacklist = blacklist;
    }


    public String getMediapath() {
        return mediapath;
    }

    public void setMediapath(String mediapath) {
        this.mediapath = mediapath;
    }

    public String getUploadDestination() {
        return getMediapath() +  "/uploads";
    }

    public void setUploadDestination(String uploadDestination) {
        this.uploadDestination = uploadDestination;
    }

    public String getWallpaperDestination() {
        return getMediapath() + "/wallpapers";
    }

    public void setWallpaperDestination(String wallpaperDestination) {
        this.wallpaperDestination = wallpaperDestination;
    }
}
