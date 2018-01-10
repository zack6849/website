package com.zack6849.website.services.storage;

import org.springframework.boot.context.properties.ConfigurationProperties;

import java.util.Arrays;
import java.util.List;

@ConfigurationProperties("storage")
public class StorageProperties {
    private String location = "media/uploads";
    private List<String> extensionBlacklist = Arrays.asList("jsp jsf jspx jstl php".split(" "));

    public String getLocation(){
        return location;
    }

    public void setLocation(String location){
        this.location = location;
    }

    public List<String> getExtensionBlacklist(){
        return this.extensionBlacklist;
    }

    public void setExtensionBlacklist(List<String> blacklist){
        this.extensionBlacklist = blacklist;
    }
}
