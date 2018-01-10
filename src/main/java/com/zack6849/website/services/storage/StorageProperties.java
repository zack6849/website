package com.zack6849.website.services.storage;

import org.springframework.boot.context.properties.ConfigurationProperties;

@ConfigurationProperties("storage")
public class StorageProperties {
    private String location = "media/uploads";

    public String getLocation(){
        return location;
    }

    public void setLocation(String location){
        this.location = location;
    }
}
