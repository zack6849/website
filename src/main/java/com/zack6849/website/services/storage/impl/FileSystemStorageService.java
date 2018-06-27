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

import java.io.File;
import java.io.IOException;
import java.net.MalformedURLException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.stream.Stream;

@Service
public class FileSystemStorageService implements StorageService{

    private final Path rootLocation;
    private final StorageProperties properties;

    @Autowired
    public FileSystemStorageService(StorageProperties properties){
        this.rootLocation = Paths.get(properties.getUploadDestination());
        this.properties = properties;
    }

    private Path getRootLocation(){
        return rootLocation;
    }

    @Override
    public Path store(MultipartFile file) {
        return store(file, getRootLocation());
    }

    @Override
    public Path store(MultipartFile file, String path) {
        return store(file, Paths.get(path));
    }

    @Override
    public Path store(MultipartFile file, Path location) {
        String filename = StringUtils.cleanPath(file.getOriginalFilename());
        try{
            if(file.isEmpty()){
                throw new StorageException("Upload Failed: file empty");
            }
            if(filename.contains("..")){
                throw new StorageException("Upload Failed: Cannot store file with relative path");
            }
            String extension = StringUtils.getFilenameExtension(file.getOriginalFilename());
            Logger.getLogger(this.getClass()).info(String.format("Checking file %s extension: %s for blacklist!", filename, extension));
            if(this.properties.getExtensionBlacklist().contains(extension)){
                Logger.getLogger(this.getClass()).info(String.format("Blocked file %s because it was of extension %s that's on blacklist!", filename, extension));
                throw new StorageException("Upload Failed: Filetype not allowed!");
            }
            //keep generating a random filename until there's one that's unique.
            while (location.resolve(filename).toFile().exists()){
                filename = new RandomStringGenerator.Builder()
                        .withinRange('0', 'z')
                        .filteredBy(CharacterPredicates.LETTERS, CharacterPredicates.DIGITS)
                        .build().generate(20);
                filename = filename + "." + extension;
            }
            Path destination = location.resolve(filename);
            Files.copy(file.getInputStream(), destination);
            return destination;
        }catch (IOException ex){
            throw new StorageException("Upload Failed: Failed to store file: " + file, ex);
        }
    }

    @Override
    public void init() {
        try {
            Logger.getLogger(this.getClass()).info("Registered FileSystem Storage Service for " + new File(this.getProperties().getMediapath()).getAbsolutePath());
            Logger.getLogger(this.getClass()).info("Blacklist: " + String.join(", ", this.getProperties().getExtensionBlacklist()));
            Files.createDirectories(Paths.get(properties.getUploadDestination()));
            Files.createDirectories(Paths.get(properties.getWallpaperDestination()));
        } catch (IOException e) {
            throw new StorageException("Failed to initialize storage provider", e);
        }
    }

    @Override
    public Stream<Path> loadAll() {
        return loadAll(getRootLocation());
    }

    @Override
    public Stream<Path> loadAll(String path) {
        return null;
    }

    @Override
    public Stream<Path> loadAll(Path location) {
        try {
            return Files.walk(location, 1)
                    .filter(path -> !path.equals(location))
                    .map(location::relativize);
        } catch (IOException e) {
            throw new StorageException("Failed to read stored files", e);
        }
    }

    @Override
    public Path load(String filename) {
        return load(filename, getRootLocation());
    }

    @Override
    public Path load(String filename, String path) {
        return load(filename, Paths.get(path));
    }

    @Override
    public Path load(String filename, Path path) {
        return path.resolve(filename);
    }

    @Override
    public Resource loadAsResource(String filename){
        return loadAsResource(filename, getRootLocation());
    }

    @Override
    public Resource loadAsResource(String filename, String path){
        return loadAsResource(filename, Paths.get(path));
    }

    @Override
    public Resource loadAsResource(String filename, Path path) {
        Path file = load(filename, path);
        try {
            Resource resource = new UrlResource(file.toUri());
            if(!resource.exists() || !resource.isReadable()){
                throw new StorageException("Couldn't read file " + path.getFileName()+ "/"+ filename);
            }
            return resource;
        } catch (MalformedURLException e) {
            throw new StorageException("Couldn't read file " + filename, e);
        }
    }

    public StorageProperties getProperties(){
        return this.properties;
    }
}
