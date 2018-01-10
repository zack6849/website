package com.zack6849.website.services.storage.impl;

import com.zack6849.website.services.storage.StorageProperties;
import com.zack6849.website.services.storage.StorageService;
import com.zack6849.website.services.storage.exception.StorageException;
import org.apache.commons.text.CharacterPredicates;
import org.apache.commons.text.RandomStringGenerator;
import org.jboss.logging.Logger;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.core.io.Resource;
import org.springframework.core.io.UrlResource;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.net.MalformedURLException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.stream.Stream;

@Service
public class FileSystemStorageService implements StorageService{

    private final Path rootLocation;

    @Autowired
    public FileSystemStorageService(StorageProperties properties){
        this.rootLocation = Paths.get(properties.getLocation());
    }

    private Path getRootLocation(){
        return rootLocation;
    }

    @Override
    public Path store(MultipartFile file) {
        String filename = StringUtils.cleanPath(file.getOriginalFilename());
        try{
            if(file.isEmpty()){
                throw new StorageException("Failed to upload file: file empty");
            }
            if(filename.contains("..")){
                throw new StorageException("Cannot store file with relative path");
            }
            //keep generating a random filename until there's one that's unique.
            while (this.rootLocation.resolve(filename).toFile().exists()){
                filename = new RandomStringGenerator.Builder()
                        .withinRange('0', 'z')
                        .filteredBy(CharacterPredicates.LETTERS, CharacterPredicates.DIGITS)
                        .build().generate(20);
                String extension = StringUtils.getFilenameExtension(file.getOriginalFilename());
                filename = filename + "." + extension;
            }
            Path destination = this.rootLocation.resolve(filename);
            Files.copy(file.getInputStream(), destination);
            return destination;
        }catch (IOException ex){
            throw new StorageException("Failed to store file: " + file, ex);
        }
    }

    @Override
    public void init() {
        try {
            Logger.getLogger(this.getClass()).info("Registered FileSystem Storage Service for " + this.rootLocation.toFile().getAbsolutePath());
            Files.createDirectories(this.rootLocation);
        } catch (IOException e) {
            throw new StorageException("Failed to initialize storage provider", e);
        }
    }

    @Override
    public Stream<Path> loadAll() {
        try {
            return Files.walk(this.rootLocation, 1)
                    .filter(path -> !path.equals(this.rootLocation))
                    .map(this.rootLocation::relativize);
        }
        catch (IOException e) {
            throw new StorageException("Failed to read stored files", e);
        }

    }
    @Override
    public Path load(String filename) {
        return this.rootLocation.resolve(filename);
    }

    @Override
    public Resource loadAsResource(String filename){
        Path file = load(filename);
        try {
            Resource resource = new UrlResource(file.toUri());
            if(!resource.exists() || !resource.isReadable()){
                throw new StorageException("Couldn't read file " + file);
            }
            return resource;
        } catch (MalformedURLException e) {
            throw new StorageException("Couldn't read file " + filename, e);
        }
    }
}
