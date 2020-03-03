#!/usr/bin/ruby

script_path = File.expand_path(__dir__)
repo_root = File.expand_path(File.join(script_path, '..', '..'))
module_root = File.join(script_path, "examples")
Dir["#{repo_root}/**/*.php"]
  .reject { |path| path.include?(module_root) }
  .each do |src_path|
    content = File.readlines(src_path)
    dst_path = File.join(module_root, 'php', File.basename(src_path))
    filtered = content.reject { |line| line =~ /^.*#(tag|end).*$/ }
    File.write(dst_path, filtered.join)
  end
Dir.chdir(module_root) { system("git diff") }
