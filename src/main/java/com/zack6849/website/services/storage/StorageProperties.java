package com.zack6849.website.services.storage;

import org.springframework.boot.context.properties.ConfigurationProperties;

import java.util.Arrays;
import java.util.List;

@ConfigurationProperties("storage")
public class StorageProperties {
    public String blacklist = "default";
    private List<String> extensionBlacklist = Arrays.asList(blacklist.split(" "));
    private String mediapath = "media";
    private String uploadDestination = getMediapath() + "/uploads";
    private String wallpaperDestination = getMediapath() + "/wallpapers";

    public List<String> getExtensionBlacklist(){
        return this.extensionBlacklist;
    }

    public void setExtensionBlacklist(List<String> blacklist){
        this.extensionBlacklist = blacklist;
    }

    public String getMediapath() {
        return mediapath;
    }

    public void setMediapath(String mediapath) {
        this.mediapath = mediapath;
    }

    public String getUploadDestination() {
        return uploadDestination;
    }

    public void setUploadDestination(String uploadDestination) {
        this.uploadDestination = uploadDestination;
    }

    public String getWallpaperDestination() {
        return wallpaperDestination;
    }

    public void setWallpaperDestination(String wallpaperDestination) {
        this.wallpaperDestination = wallpaperDestination;
    }
}
